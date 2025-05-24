#include <ArduinoJson.h>
#include <WiFi.h>
#include <MQTT.h>
#include <TinyGPSPlus.h>
#include "secrets.h"

// Wi-Fi credentials
const char* ssid = "";
const char* password = "";

// MQTT Broker details
const char* mqttServer = "broker.emqx.io";
const int mqttPort = 1883;
const char* mqttClientID = "Unique ID"; //Optional

WiFiClient net;
MQTTClient client;

// MQTT Topics
const char* vibrationTopic = "";
const char* gpsTopic = "";
const char* buzzerTopic = "";

// Define sensors and LED pins
#define SENSOR1_PIN  19
#define SENSOR2_PIN  18
#define LED1_PIN     4
#define LED2_PIN     26
#define BUZZER       27

// GPS
#define RXPin 16
#define TXPin 17
#define GPSBaud 9600
TinyGPSPlus gps;
HardwareSerial gpsSerial(2);

// Telegram Bot Details
const char* telegramBotToken = "";  
const char* telegramChatID = ""; 

void messageReceived(String &topic, String &payload) {
    Serial.println("📩 Message received on topic: " + topic);
    Serial.println("📜 Payload: " + payload);
    
    if (topic == buzzerTopic) {
        if (payload == "ON") {
            digitalWrite(BUZZER, HIGH);
            Serial.println("🔔 Buzzer Activated!");
        } else if (payload == "OFF") {
            digitalWrite(BUZZER, LOW);
            Serial.println("🔕 Buzzer Deactivated!");
        }
    }
}

void setup() {
    Serial.begin(115200);

    pinMode(SENSOR1_PIN, INPUT);
    pinMode(SENSOR2_PIN, INPUT);
    pinMode(LED1_PIN, OUTPUT);
    pinMode(LED2_PIN, OUTPUT);
    pinMode(BUZZER, OUTPUT);
    
    digitalWrite(LED1_PIN, LOW);
    digitalWrite(LED2_PIN, LOW);
    digitalWrite(BUZZER, LOW);

    gpsSerial.begin(GPSBaud, SERIAL_8N1, RXPin, TXPin);

    // Connect to Wi-Fi
    connectWiFi();

    // Set up MQTT
    client.begin(mqttServer, net);
    client.onMessage(messageReceived);
    connectMQTT();

    // Subscribe to buzzer topic
    client.subscribe(buzzerTopic);
}

void loop() {
    client.loop();  // Keep MQTT connection alive

    int sensor1State = digitalRead(SENSOR1_PIN);
    int sensor2State = digitalRead(SENSOR2_PIN);

    if (sensor1State == HIGH) {
        Serial.println("🚨 Region 1 detected!");
        digitalWrite(LED1_PIN, HIGH);
        sendVibrationAlert("Region 1", 1);
        sendGPSData();
        delay(500);
        digitalWrite(LED1_PIN, LOW);
    }

    if (sensor2State == HIGH) {
        Serial.println("🚨 Region 2 detected!");
        digitalWrite(LED2_PIN, HIGH);
        sendVibrationAlert("Region 2", 1);
        sendGPSData();
        delay(500);
        digitalWrite(LED2_PIN, LOW);
    }
}

// ✅ Function to Send Vibration Alert to MQTT
void sendVibrationAlert(const char* region, int state) {
    StaticJsonDocument<200> jsonDoc;
    jsonDoc["region"] = region;
    jsonDoc["state"] = state;

    char jsonPayload[256];
    serializeJson(jsonDoc, jsonPayload);

    client.publish(vibrationTopic, jsonPayload);
    Serial.println("✅ Vibration Alert Sent to MQTT: " + String(jsonPayload));
}

// ✅ Function to Get GPS Data and Send to MQTT
void sendGPSData() {
    float latitude = 0.0, longitude = 0.0;
    unsigned long startTime = millis();

    while (millis() - startTime < 2000) {  // Wait up to 2 sec for GPS update
        while (gpsSerial.available()) {
            gps.encode(gpsSerial.read());
        }
        
        if (gps.location.isUpdated()) {
            latitude = gps.location.lat();
            longitude = gps.location.lng();

            StaticJsonDocument<200> jsonDoc;
            jsonDoc["latitude"] = latitude;
            jsonDoc["longitude"] = longitude;

            char jsonPayload[256];
            serializeJson(jsonDoc, jsonPayload);

            client.publish(gpsTopic, jsonPayload);
            Serial.println("✅ GPS Data Sent to MQTT: " + String(jsonPayload));

            sendTelegram(latitude, longitude);
            return;
        }
    }

    Serial.println("✅ GPS Data Sent to MQTT");
}

// ✅ Function to Send Google Maps Link to Telegram
void sendTelegram(float lat, float lon) {
    String telegramMessage = "🚨 *Durian Fall Alert!* 🌳\n";
    telegramMessage += "🌍 *Location*: [" + String(lat, 6) + ", " + String(lon, 6) + "]\n";
    telegramMessage += "🔗 *Google Maps*: [View Location](https://www.google.com/maps?q=" + String(lat, 6) + "," + String(lon, 6) + ")";

    String url = "https://api.telegram.org/bot" + String(telegramBotToken) +
                 "/sendMessage?chat_id=" + String(telegramChatID) +
                 "&text=" + telegramMessage + "&parse_mode=Markdown";

    Serial.println("📨 Sending Telegram...");
    sendHTTP(url);
}

// ✅ Function to Send HTTP Request
void sendHTTP(String url) {
    WiFiClient client;
    if (!client.connect("api.telegram.org", 80)) {
        Serial.println("⚠ Connection to Telegram failed.");
        return;
    }

    client.print(String("GET ") + url + " HTTP/1.1\r\n" +
                 "Host: api.telegram.org\r\n" +
                 "Connection: close\r\n\r\n");

    delay(100);
    while (client.available()) {
        Serial.print(client.readStringUntil('\r'));
    }
}

// ✅ Function to Connect Wi-Fi
void connectWiFi() {
    Serial.print("Connecting to Wi-Fi...");
    WiFi.begin(ssid, password);
    while (WiFi.status() != WL_CONNECTED) {
        delay(1000);
        Serial.print(".");
    }
    Serial.println("\n✅ Connected to Wi-Fi!");
}

// ✅ Function to Connect to MQTT Broker
void connectMQTT() {
    Serial.print("Connecting to MQTT...");
    while (!client.connect(mqttClientID)) {
        Serial.print(".");
        delay(1000);
    }
    Serial.println("\n✅ Connected to MQTT!");
}
