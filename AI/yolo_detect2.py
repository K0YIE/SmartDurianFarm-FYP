# -*- coding: utf-8 -*-
import os
import sys
import argparse
import glob
import time
import json
import paho.mqtt.client as mqtt
import cv2
import numpy as np
from ultralytics import YOLO
import RPi.GPIO as GPIO

MQTT_BROKER = "broker.emqx.io"
MQTT_PORT = 1883
MQTT_TOPIC_MONKEY = "monkey/detection"
MQTT_TOPIC_DURIAN = "durian/count"

client = mqtt.Client()
try:
    client.connect(MQTT_BROKER, MQTT_PORT, 60)
    mqtt_available = True
except Exception as e:
    print(f"MQTT connection failed: {e}")
    mqtt_available = False

BUZZER_PIN = 27
GPIO.setmode(GPIO.BCM)
GPIO.setup(BUZZER_PIN, GPIO.OUT)
GPIO.output(BUZZER_PIN, GPIO.LOW)

parser = argparse.ArgumentParser()
parser.add_argument('--model', required=True, help='Path to YOLO model file')
parser.add_argument('--source', required=True, help='Image or video source')
parser.add_argument('--thresh', default=0.5, type=float, help='Minimum confidence threshold')
parser.add_argument('--resolution', default=None, help='Resolution in WxH')
parser.add_argument('--record', action='store_true', help='Record results')
args = parser.parse_args()

model_path = args.model
img_source = args.source
min_thresh = args.thresh
user_res = args.resolution
record = args.record

if not os.path.exists(model_path):
    print('ERROR: Model path is invalid.')
    sys.exit(0)

model = YOLO(model_path, task='detect')
labels = model.names

img_ext_list = ['.jpg', '.jpeg', '.png', '.bmp']
vid_ext_list = ['.avi', '.mp4', '.mkv', '.wmv']

if os.path.isdir(img_source):
    source_type = 'folder'
elif os.path.isfile(img_source):
    _, ext = os.path.splitext(img_source)
    source_type = 'image' if ext in img_ext_list else 'video' if ext in vid_ext_list else None
elif 'usb' in img_source:
    source_type = 'usb'
    usb_idx = int(img_source[3:])
elif 'picamera' in img_source:
    source_type = 'picamera'
    picam_idx = int(img_source[8:])
else:
    print(f'Invalid input: {img_source}')
    sys.exit(0)

if user_res:
    resW, resH = map(int, user_res.split('x'))

if record:
    if source_type not in ['video', 'usb']:
        print('Recording only works for video and camera sources.')
        sys.exit(0)
    if not user_res:
        print('Specify resolution for recording.')
        sys.exit(0)
    recorder = cv2.VideoWriter('demo1.avi', cv2.VideoWriter_fourcc(*'MJPG'), 30, (resW, resH))

if source_type == 'image':
    imgs_list = [img_source]
elif source_type == 'folder':
    imgs_list = [f for f in glob.glob(img_source + '/*') if os.path.splitext(f)[1] in img_ext_list]
elif source_type in ['video', 'usb']:
    cap = cv2.VideoCapture(img_source if source_type == 'video' else usb_idx)
    if user_res:
        cap.set(3, resW)
        cap.set(4, resH)
elif source_type == 'picamera':
    from picamera2 import Picamera2
    cap = Picamera2()
    cap.configure(cap.create_video_configuration(main={"format": 'RGB888', "size": (resW, resH)}))
    cap.start()

bbox_colors = [(164, 120, 87), (68, 148, 228), (93, 97, 209), (178, 182, 133), (88, 159, 106)]
frame_rate_buffer = []
fps_avg_len = 200
img_count = 0
monkey_detected = False

while True:
    t_start = time.perf_counter()

    if source_type in ['image', 'folder']:
        if img_count >= len(imgs_list):
            print('All images processed.')
            break
        frame = cv2.imread(imgs_list[img_count])
        img_count += 1
    else:
        ret, frame = cap.read()
        if not ret:
            print('End of video or error.')
            break

    if user_res:
        frame = cv2.resize(frame, (resW, resH))

    results = model(frame, verbose=False)
    detections = results[0].boxes
    object_count = 0
    durian_count = 0
    monkey_present = False

    for i in range(len(detections)):
        xyxy = detections[i].xyxy.cpu().numpy().squeeze().astype(int)
        classidx = int(detections[i].cls.item())
        classname = labels[classidx]
        conf = detections[i].conf.item()

        if conf > min_thresh:
            color = bbox_colors[classidx % len(bbox_colors)]
            cv2.rectangle(frame, (xyxy[0], xyxy[1]), (xyxy[2], xyxy[3]), color, 2)
            label = f'{classname}: {int(conf * 100)}%'
            cv2.putText(frame, label, (xyxy[0], xyxy[1] - 7), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 0), 1)
            object_count += 1

            if classname.lower() == 'monkey':
                monkey_present = True
            elif classname.lower() == 'durian':
                durian_count += 1

    if mqtt_available:
        timestamp = time.strftime("%Y-%m-%d %H:%M:%S")
        mqtt_msg = json.dumps({"durian_count": durian_count, "timestamp": timestamp})
        client.publish(MQTT_TOPIC_DURIAN, mqtt_msg)

    if monkey_present and not monkey_detected:
        GPIO.output(BUZZER_PIN, GPIO.HIGH)
        try:
            if mqtt_available:
                client.publish(MQTT_TOPIC_MONKEY, "ON")
                print("Monkey detected! Sent MQTT 'ON' and activated buzzer.")
        except Exception as e:
            print(f"MQTT failed: {e}")
        monkey_detected = True

    elif not monkey_present and monkey_detected:
        GPIO.output(BUZZER_PIN, GPIO.LOW)
        try:
            if mqtt_available:
                client.publish(MQTT_TOPIC_MONKEY, "OFF")
                print("No monkey detected. Sent MQTT 'OFF' and deactivated buzzer.")
        except Exception as e:
            print(f"MQTT failed: {e}")
        monkey_detected = False

    current_fps = 1 / (time.perf_counter() - t_start)
    frame_rate_buffer.append(current_fps)
    if len(frame_rate_buffer) > fps_avg_len:
        frame_rate_buffer.pop(0)
    avg_frame_rate = np.mean(frame_rate_buffer)

    cv2.putText(frame, f'Objects: {object_count}', (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 255), 2)
    cv2.putText(frame, f'Durian Count: {durian_count}', (10, 60), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 0), 2)
    cv2.putText(frame, f'FPS: {avg_frame_rate:.2f}', (10, 90), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
    cv2.imshow('YOLO Detection', frame)

    if record:
        recorder.write(frame)

    key = cv2.waitKey(5 if source_type != 'image' else 0)
    if key in [ord('q'), ord('Q')]:
        break
    elif key in [ord('p'), ord('P')]:
        cv2.imwrite('capture.png', frame)

if source_type in ['video', 'usb']:
    cap.release()
elif source_type == 'picamera':
    cap.stop()
if record:
    recorder.release()
cv2.destroyAllWindows()
GPIO.output(BUZZER_PIN, GPIO.LOW)
GPIO.cleanup()
if mqtt_available:
    client.disconnect()
