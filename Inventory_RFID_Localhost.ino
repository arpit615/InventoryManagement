#include <ESP8266WiFi.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <SPI.h>
#include <MFRC522.h>
#include <ESP8266HTTPClient.h>
#include <SoftwareSerial.h>
#include <TinyGPS++.h>

// WiFi credentials
const char* ssid = "project";
const char* password = "project007";

// Server URL
const String server = "http://192.168.141.206/inventory/update_inventory.php";

// RC522 setup
#define SS_PIN D3
#define RST_PIN D0
MFRC522 mfrc522(SS_PIN, RST_PIN);

// LCD setup
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Inventory
int inventory[5] = {10, 10, 10, 10, 10};

// UID Tags and Items
const byte knownUIDs[5][4] = {
  {0x0C, 0x90, 0x0F, 0x05}, // Item A
  {0x15, 0x87, 0x0F, 0x05}, // Item B
  {0xAB, 0x06, 0x15, 0x05}, // Item C
  {0xCE, 0xF1, 0xE4, 0x00}, // Item D
  {0x87, 0x15, 0xE9, 0x00}  // Item E
};
const char* itemNames[5] = {"Item_A", "Item_B", "Item_C", "Item_D", "Item_E"};

// GPS setup
TinyGPSPlus gps;

// Default coordinates
const double defaultLat = 28.6109;
const double defaultLon = 77.0385;

unsigned long lastScanTime = 0;
byte lastUID[4] = {0};

void setup() {
  Serial.begin(115200);
  SPI.begin();
  mfrc522.PCD_Init();

  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Connecting WiFi");

  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("System Ready");
  lcd.setCursor(0, 1);
  lcd.print("Scan Item...");
  delay(2000);
  showScanPrompt();
}

void loop() {
  // Non-blocking GPS reading
  if (Serial.available()) {
    gps.encode(Serial.read());
  }

  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial())
    return;

  // Prevent duplicate scans
  if (millis() - lastScanTime < 2000 && memcmp(mfrc522.uid.uidByte, lastUID, 4) == 0)
    return;

  memcpy(lastUID, mfrc522.uid.uidByte, 4);
  lastScanTime = millis();

  int index = identifyItem(mfrc522.uid.uidByte);
  if (index != -1) {
    if (inventory[index] > 0) {
      inventory[index]--;

      // Display item scanned and count
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print(itemNames[index]);
      lcd.setCursor(0, 1);
      lcd.print("Left: ");
      lcd.print(inventory[index]);
      Serial.println("Scanned: " + String(itemNames[index]) + ", Left: " + String(inventory[index]));

      // Get coordinates
      double latitude, longitude;
      if (gps.location.isValid()) {
        latitude = gps.location.lat();
        longitude = gps.location.lng();
      } else {
        latitude = defaultLat;
        longitude = defaultLon;
      }

      // Upload to server
      String response = sendToServer(itemNames[index], inventory[index], latitude, longitude);

      // Display server response briefly
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Uploaded!");
      lcd.setCursor(0, 1);
      lcd.print("Resp: ");
      lcd.print(response.substring(0, 10)); // Limit display size
      delay(1500);

      // Return to scan prompt
      showScanPrompt();
    } else {
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print(itemNames[index]);
      lcd.setCursor(0, 1);
      lcd.print("OUT OF STOCK");
      Serial.println("OUT OF STOCK: " + String(itemNames[index]));
      delay(2000);
      showScanPrompt();
    }
  } else {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Unknown Tag");
    Serial.println("Unknown Tag Scanned");
    delay(2000);
    showScanPrompt();
  }

  mfrc522.PICC_HaltA();
}

int identifyItem(byte *uid) {
  for (int i = 0; i < 5; i++) {
    if (memcmp(uid, knownUIDs[i], 4) == 0)
      return i;
  }
  return -1;
}

String sendToServer(const char* item, int left, double latitude, double longitude) {
  if (WiFi.status() != WL_CONNECTED) {
    return "WiFiErr";
  }

  HTTPClient http;
  WiFiClient client;

  String url = server + "?item=" + String(item)
                     + "&left=" + String(left)
                     + "&latitude=" + String(latitude, 6)
                     + "&longitude=" + String(longitude, 6);
  Serial.println("URL: " + url);

  http.begin(client, url);
  int httpCode = http.GET();
  String response = "";

  if (httpCode > 0) {
    response = http.getString();
    Serial.println("Server Response: " + response);
  } else {
    response = "Err:" + String(httpCode);
    Serial.println("HTTP Error: " + response);
  }

  http.end();
  return response;
}

void showScanPrompt() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Scan Item...");
  lcd.setCursor(0, 1);
  lcd.print("Ready...");
}
