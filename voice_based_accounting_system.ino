#include <WiFi.h>
#include <HTTPClient.h>

// WiFi credentials
const char* ssid = "Xiaomi 11T";
const char* password = "j9546028";

// Cohere API settings
const char* cohere_api_url = "https://api.cohere.ai/generate";
const char* cohere_api_key = "EmJ6qeSTVX3zajgAdRU1ZV6pBTq6v8J9STFzDtCz"; // Replace with your API key

void setup() {
  Serial.begin(115200);
  connectToWiFi();
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
    String response = sendToCohere(inputText);
    Serial.println("LLM Response:");
    Serial.println(response);
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
}

String sendToCohere(String inputText) {
  if (WiFi.status() != WL_CONNECTED) {
    return "WiFi not connected!";
  }

  HTTPClient http;
  http.begin(cohere_api_url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", String("Bearer ") + cohere_api_key);

  // Prepare the structured prompt with validation instructions
  String prompt = 
    "Extract the following details from the text:\\n"
    "- What kind of food did the user eat?\\n"
    "- What type of food is it (choose one: breakfast, lunch, dinner, snack)?\\n"
    "- What is the cost in New Taiwan Dollars (NTD)?\\n\\n"
    "### Instructions:\\n"
    "1. Return the result as: [Food, Type, Cost].\\n"
    "2. If any field is missing or unclear, respond with:\\n"
    "   - \\\"Missing [field_name]: Please provide the [field_name].\\\"\\n"
    "3. Ensure all three fields are present and valid before returning the result.\\n\\n"
    "### Examples:\\n"
    "Input: \\\"I had a soy milk for breakfast, it cost me 30 NTD.\\\"\\n"
    "Output: [soy milk, breakfast, 30]\\n\\n"
    "Input: \\\"I bought a bento for lunch at 100 NTD.\\\"\\n"
    "Output: [bento, lunch, 100]\\n\\n"
    "Input: \\\"I ate chips as a snack, it was 50 NTD.\\\"\\n"
    "Output: [chips, snack, 50]\\n\\n"
    "Input: \\\"I bought noodles, costing me 70 NTD.\\\"\\n"
    "Output: Missing Type: Please specify if this is breakfast, lunch, dinner, or snack.\\n\\n"
    "### Input:\\n" + inputText + "\\n\\n### Output:";

  // Build the JSON payload
  String payload = "{";
  payload += "\"model\":\"command-xlarge\",";
  payload += "\"prompt\":\"" + prompt + "\",";
  payload += "\"max_tokens\":100,";
  payload += "\"temperature\":0.5";
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

