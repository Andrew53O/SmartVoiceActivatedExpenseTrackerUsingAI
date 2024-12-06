#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <WiFi.h>
#include <HTTPClient.h>

// OLED display settings
#define SCREEN_WIDTH 128 // OLED width in pixels
#define SCREEN_HEIGHT 64 // OLED height in pixels
#define OLED_RESET -1    // Reset pin (not used)
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// WiFi credentials
const char* ssid = "Your_SSID";
const char* password = "Your_PASSWORD";0

// Cohere API settings
const char* cohere_api_url = "https://api.cohere.ai/generate";
const char* cohere_api_key = "your_cohere_api_key"; // Replace with your Cohere API key

void setup() {
  Serial.begin(115200);
  connectToWiFi();

  // Initialize the OLED display
  if (!display.begin(SSD1306_I2C_ADDRESS, 0x3C)) { // Default I2C address for OLED
    Serial.println("SSD1306 allocation failed");
    for (;;); // Stop execution if OLED initialization fails
  }

  // Clear the display buffer
  display.clearDisplay();
  display.display();
  showTextOnOLED("Ready for input!");
}

void loop() {
  Serial.println("Enter text to send to the LLM:");
  while (!Serial.available()) {
    delay(100); // Wait for user input
  }

  // Read user input from Serial Monitor
  String inputText = Serial.readStringUntil('\n');
  inputText.trim(); // Remove extra whitespace

  if (inputText.length() > 0) {
    showTextOnOLED("Processing...");
    String response = sendToCohere(inputText);
    Serial.println("LLM Response:");
    Serial.println(response);

    // Display the response on the OLED
    showTextOnOLED(response);
  }
}

void connectToWiFi() {
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected to WiFi!");
  showTextOnOLED("WiFi Connected!");
}

String sendToCohere(String inputText) {
  if (WiFi.status() != WL_CONNECTED) {
    return "WiFi not connected!";
  }

  HTTPClient http;
  http.begin(cohere_api_url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", String("Bearer ") + cohere_api_key);

  // Prepare the structured prompt
  String prompt = 
    "Extract the following details from the text:\\n"
    "- What kind of food did the user eat?\\n"
    "- What type of food is it (choose one: breakfast, lunch, dinner, snack)?\\n"
    "- What is the cost in New Taiwan Dollars (NTD)?\\n\\n"
    "Return the result as:\\n[Food, Type, Cost]\\n\\n"
    "### Input:\\n" + inputText + "\\n\\n### Output:";

  // Build the JSON payload
  String payload = "{";
  payload += "\"model\":\"command-xlarge\",";
  payload += "\"prompt\":\"" + prompt + "\",";
  payload += "\"max_tokens\":50,";
  payload += "\"temperature\":0.7";
  payload += "}";

  // Send POST request
  int httpResponseCode = http.POST(payload);

  String response = "";
  if (httpResponseCode > 0) {
    response = http.getString(); // Get the response payload
  } else {
    response = "Error in HTTP request: " + String(httpResponseCode);
  }

  http.end(); // Free resources
  return response;
}

void showTextOnOLED(String text) {
  display.clearDisplay();
  display.setTextSize(1);       // Text size
  display.setTextColor(WHITE); // Text color
  display.setCursor(0, 0);     // Start at top-left corner

  // Display text on OLED
  display.println(text);
  display.display();
}
