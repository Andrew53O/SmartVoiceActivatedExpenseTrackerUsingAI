#include <Arduino.h>
#include <driver/i2s.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <stdlib.h>
#include <string.h>

// ====== Your WiFi Credentials ======
// const char* ssid = "Xiaomi 11T";
// const char* password = "j9546028";

const char *ssid = "SUR";
const char *password = "sunsurmat";

// ====== Deepgram API Key ======
const char *deepgram_api_key = "88b9708e621dc5345c55ba8c889ff573cdb6b0b5";

// ====== Cohere API Key ======
const char *cohere_api_key = "0Khe705NuHP7naQApcEa018DDWM42ti7GQ8tfXsj";

// ====== OLED ======
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// ====== I2S Pins ======
#define I2S_BCLK 14
#define I2S_WS 15
#define I2S_DIN 32

// ====== Clap Detection ======
const int CLAP_THRESHOLD = 3000;
const int CLAP_COOLDOWN = 300;
unsigned long firstClapTime = 0;
bool waitingForSecondClap = false;
unsigned long lastClapTime = 0;

// ====== Recording Params ======
#define SAMPLE_RATE 6000
#define RECORD_SEGMENT_SECONDS 5
#define NUM_SAMPLES_PER_SEGMENT (SAMPLE_RATE * RECORD_SEGMENT_SECONDS)
int16_t audioBuffer[NUM_SAMPLES_PER_SEGMENT];

// WAV header
struct WAVHeader
{
  char riff[4];
  uint32_t chunkSize;
  char wave[4];
  char fmt[4];
  uint32_t subchunk1Size;
  uint16_t audioFormat;
  uint16_t numChannels;
  uint32_t sampleRate;
  uint32_t byteRate;
  uint16_t blockAlign;
  uint16_t bitsPerSample;
  char data[4];
  uint32_t subchunk2Size;
};

// Global transcripts
String transcript1 = "";
String transcript2 = "";

// ====== Functions ======

void displayText(String text)
{
  display.clearDisplay();
  display.setCursor(0, 0);
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);

  int maxCharsPerLine = 21;
  int currentY = 0;
  int lineHeight = 8; // Height of one line in pixels
  int maxLines = 8;   // Maximum lines that can fit on display (64/8)

  // Split text by newlines first
  String lines[8]; // Array to store lines
  int lineCount = 0;

  int startPos = 0;
  int nextNewline;

  // Handle explicit line breaks first
  while ((nextNewline = text.indexOf('\n', startPos)) != -1 && lineCount < maxLines)
  {
    String segment = text.substring(startPos, nextNewline);
    // Word wrap long segments
    while (segment.length() > maxCharsPerLine && lineCount < maxLines)
    {
      int wrapPos = maxCharsPerLine;
      // Look for last space before maxCharsPerLine
      for (int i = maxCharsPerLine; i >= 0; i--)
      {
        if (segment[i] == ' ')
        {
          wrapPos = i;
          break;
        }
      }
      lines[lineCount++] = segment.substring(0, wrapPos);
      segment = segment.substring(wrapPos + 1);
    }
    if (lineCount < maxLines && segment.length() > 0)
    {
      lines[lineCount++] = segment;
    }
    startPos = nextNewline + 1;
  }

  // Handle remaining text
  if (startPos < text.length() && lineCount < maxLines)
  {
    String segment = text.substring(startPos);
    while (segment.length() > maxCharsPerLine && lineCount < maxLines)
    {
      int wrapPos = maxCharsPerLine;
      for (int i = maxCharsPerLine; i >= 0; i--)
      {
        if (segment[i] == ' ')
        {
          wrapPos = i;
          break;
        }
      }
      lines[lineCount++] = segment.substring(0, wrapPos);
      segment = segment.substring(wrapPos + 1);
    }
    if (lineCount < maxLines && segment.length() > 0)
    {
      lines[lineCount++] = segment;
    }
  }

  // Display the processed lines
  for (int i = 0; i < lineCount; i++)
  {
    display.setCursor(0, i * lineHeight);
    display.println(lines[i]);
  }

  display.display();
}

void setupDisplay()
{
  Wire.begin(GPIO_NUM_21, GPIO_NUM_22);

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C))
  {
    Serial.println("SSD1306 allocation failed");
    while (1)
      ;
  }
  displayText("Clap 2 times to start");
  Serial.println("OLED initialized.");
}

void setupI2S()
{
  i2s_config_t i2s_config = {
      .mode = (i2s_mode_t)(I2S_MODE_MASTER | I2S_MODE_RX),
      .sample_rate = SAMPLE_RATE,
      .bits_per_sample = I2S_BITS_PER_SAMPLE_32BIT,
      .channel_format = I2S_CHANNEL_FMT_ONLY_LEFT,
      .communication_format = I2S_COMM_FORMAT_I2S,
      .intr_alloc_flags = ESP_INTR_FLAG_LEVEL1,
      .dma_buf_count = 8,
      .dma_buf_len = 64,
      .use_apll = false,
      .tx_desc_auto_clear = false,
      .fixed_mclk = 0};
  i2s_pin_config_t pin_config = {
      .bck_io_num = I2S_BCLK,
      .ws_io_num = I2S_WS,
      .data_out_num = I2S_PIN_NO_CHANGE,
      .data_in_num = I2S_DIN};
  if (i2s_driver_install(I2S_NUM_0, &i2s_config, 0, NULL) != ESP_OK)
  {
    Serial.println("I2S install failed");
    while (1)
      ;
  }
  if (i2s_set_pin(I2S_NUM_0, &pin_config) != ESP_OK)
  {
    Serial.println("I2S set pin failed");
    while (1)
      ;
  }
  i2s_zero_dma_buffer(I2S_NUM_0);
  Serial.println("I2S initialized.");
}

int32_t readI2SSample()
{
  int32_t sample = 0;
  size_t bytesRead;
  i2s_read(I2S_NUM_0, &sample, sizeof(sample), &bytesRead, portMAX_DELAY);
  return sample;
}

void recordAudio()
{
  Serial.println("開始錄音...");
  displayText("Recording...");
  for (int i = 0; i < NUM_SAMPLES_PER_SEGMENT; i++)
  {
    int32_t s = readI2SSample();
    audioBuffer[i] = (int16_t)(s >> 16);
  }
  Serial.println("錄音完成!");
  displayText("Recording Done!");
}

void createWavHeader(uint8_t *buffer, uint32_t totalAudioLen)
{
  WAVHeader header;
  memcpy(header.riff, "RIFF", 4);
  memcpy(header.wave, "WAVE", 4);
  memcpy(header.fmt, "fmt ", 4);
  memcpy(header.data, "data", 4);

  header.subchunk1Size = 16;
  header.audioFormat = 1;
  header.numChannels = 1;
  header.sampleRate = SAMPLE_RATE;
  header.bitsPerSample = 16;
  header.byteRate = header.sampleRate * header.numChannels * (header.bitsPerSample / 8);
  header.blockAlign = header.numChannels * (header.bitsPerSample / 8);

  header.subchunk2Size = totalAudioLen;
  header.chunkSize = 36 + header.subchunk2Size;

  memcpy(buffer, &header, sizeof(WAVHeader));
}

uint8_t *createWavData()
{
  uint32_t pcmDataSize = NUM_SAMPLES_PER_SEGMENT * sizeof(int16_t);
  uint32_t wavSize = sizeof(WAVHeader) + pcmDataSize;

  Serial.print("Attempting to allocate WAV data (");
  Serial.print(wavSize);
  Serial.println(" bytes).");

  uint8_t *wavData = (uint8_t *)malloc(wavSize);
  if (!wavData)
  {
    Serial.println("WAV記憶體配置失敗");
    displayText("WAV mem fail");
    return NULL;
  }

  createWavHeader(wavData, pcmDataSize);
  memcpy(wavData + sizeof(WAVHeader), audioBuffer, pcmDataSize);
  Serial.println("WAV data created successfully.");
  return wavData;
}

String parseTranscriptString(String payload)
{
  int startIndex = payload.indexOf("\"transcript\":\"");
  if (startIndex != -1)
  {
    startIndex += 14;
    int endIndex = payload.indexOf("\"", startIndex);
    if (endIndex != -1)
    {
      return payload.substring(startIndex, endIndex);
    }
  }
  return "";
}

String sendToDeepgram()
{
  uint8_t *wavData = createWavData();
  if (!wavData)
    return "";

  Serial.print("Free heap: ");
  Serial.println(ESP.getFreeHeap());

  displayText("Uploading...");

  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  String url = "https://api.deepgram.com/v1/listen?language=en-US";
  http.begin(client, url);
  http.addHeader("Authorization", String("Token ") + deepgram_api_key);
  http.addHeader("Content-Type", "audio/wav");
  http.setTimeout(20000);

  int wavSize = sizeof(WAVHeader) + (NUM_SAMPLES_PER_SEGMENT * sizeof(int16_t));
  Serial.print("上傳音訊大小: ");
  Serial.println(wavSize);

  Serial.print("開始上傳...");
  int httpResponseCode = http.POST(wavData, wavSize);

  String result = "";
  if (httpResponseCode > 0)
  {
    Serial.print("HTTP 響應碼: ");
    Serial.println(httpResponseCode);
    String payload = http.getString();
    Serial.println("回應內容:");
    Serial.println(payload);
    result = parseTranscriptString(payload);
    Serial.print("Transcription: ");
    Serial.println(result);
  }
  else
  {
    Serial.print("HTTP error code: ");
    Serial.println(httpResponseCode);
    Serial.println(http.errorToString(httpResponseCode));
    displayText("HTTP error:\n" + String(httpResponseCode));
  }

  free(wavData);
  http.end();
  return result;
}

// 將合併的轉錄結果送到Cohere API解析
void sendToComputer(String food, int price, String meal)
{
  // Prepare JSON payload
  String jsonData = "{";
  jsonData += "\"food\":\"" + food + "\",";
  jsonData += "\"price\":\"" + String(price) + "\",";
  jsonData += "\"meal_type\":\"" + meal + "\"";
  jsonData += "}";

  // Send data to the server
  WiFiClient client;
  HTTPClient http;
  String url = "http://192.168.121.66:8888/web/upload_data.php"; // Replace with your actual IP
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");

  int httpResponseCode = http.POST(jsonData);

  // Before HTTP request, add debug print
  Serial.println("Price value: " + String(price));

  // Fix the display text formatting
  if (httpResponseCode > 0)
  {
    String response = http.getString();
    Serial.println("Server response: " + response);

    // Convert price to String explicitly and build message
    String message = "Data sent to server!\n\n";
    message += "Food: " + food + "\n";
    message += "Price: " + String(price) + "\n"; // Explicit conversion
    message += "Meal: " + meal;

    displayText(message);
  }
  else
  {
    Serial.print("HTTP Error code: ");
    Serial.println(httpResponseCode);
    Serial.print("Error message: ");
    Serial.println(http.errorToString(httpResponseCode));
    displayText("Server error:\n" + String(httpResponseCode));
  }

  http.end();
}

void sendToCohere(String fullTranscript)
{
  String prompt =
      "Given the following transcript:\\n" + fullTranscript + "\\n\\n"
                                                              "Extract and identify the following information in JSON format. "
                                                              "If information is not found, use empty string for text fields and 0 for price:\\n"
                                                              "{\\n"
                                                              "  \\\"food\\\": \\\"<the food mentioned or empty string if not found>\\\",\\n"
                                                              "  \\\"price\\\": <the price in numbers only or 0 if not found>,\\n"
                                                              "  \\\"meal_type\\\": \\\"<breakfast/lunch/dinner/snack or empty string if not found>\\\"\\n"
                                                              "}\\n\\n"
                                                              "Return only the JSON object and no extra text. "
                                                              "The price must be a number without quotes, all other fields use quotes.";

  displayText("Asking Cohere...");

  WiFiClientSecure client;
  client.setInsecure();
  HTTPClient http;
  String url = "https://api.cohere.com/v1/generate";
  http.begin(client, url);
  http.addHeader("Authorization", String("Bearer ") + cohere_api_key);
  http.addHeader("Content-Type", "application/json");

  String requestData = "{";
  requestData += "\"model\":\"command-xlarge-nightly\",";
  requestData += "\"prompt\":\"" + prompt + "\",";
  requestData += "\"max_tokens\":100,";
  requestData += "\"temperature\":0.0";
  requestData += "}";

  int httpResponseCode = http.POST((uint8_t *)requestData.c_str(), requestData.length());
  if (httpResponseCode > 0)
  {
    Serial.print("Cohere HTTP code: ");
    Serial.println(httpResponseCode);
    String payload = http.getString();
    Serial.println("Cohere response:");
    Serial.println(payload);

    // 從 payload 中尋找 "text":" 的位置
    int textKeyPos = payload.indexOf("\"text\":\"");
    String extracted = "";

    if (textKeyPos != -1)
    {
      int start = textKeyPos + 8; // 跳過 "text":"
      bool inEscape = false;      // 標記上一個字元是否為反斜線
      char c;
      for (int i = start; i < payload.length(); i++)
      {
        c = payload.charAt(i);

        if (inEscape)
        {
          // 前一字元是 '\\'，此字元無論如何都加入字串
          extracted += c;
          inEscape = false;
        }
        else
        {
          if (c == '\\')
          {
            // 如果遇到 '\\'，下個字元要特別處理
            inEscape = true;
          }
          else if (c == '\"')
          {
            // 遇到非轉義的 '"' 表示字串結束
            break;
          }
          else
          {
            // 一般字元，直接加入
            extracted += c;
          }
        }
      }
    }

    // 將轉義字元替換回正常字元
    extracted.replace("\\n", "\n");
    extracted.replace("\\\"", "\"");

    Serial.println("Extracted JSON:");
    Serial.println(extracted);

    // 現在 extracted 應該是完整的 JSON 顯示
    // 分析 extracted JSON 中的 food, price, meal_type
    String food = "";
    int price = 0;
    String meal = "";

    int fIndex = extracted.indexOf("\"food\"");
    if (fIndex != -1)
    {
      int colon = extracted.indexOf(":", fIndex);
      int quote1 = extracted.indexOf("\"", colon);
      int quote2 = extracted.indexOf("\"", quote1 + 1);
      if (quote1 != -1 && quote2 != -1)
      {
        food = extracted.substring(quote1 + 1, quote2);
      }
    }

    int pIndex = extracted.indexOf("\"price\"");
    if (pIndex != -1)
    {
      int colon = extracted.indexOf(":", pIndex);
      int comma = extracted.indexOf(",", colon);
      if (comma == -1)
      { // Handle case where price is last field
        comma = extracted.indexOf("}", colon);
      }
      if (colon != -1 && comma != -1)
      {
        String priceStr = extracted.substring(colon + 1, comma);
        // Remove whitespace and convert to integer
        price = priceStr.toInt();
      }
    }

    int mIndex = extracted.indexOf("\"meal_type\"");
    if (mIndex != -1)
    {
      int colon = extracted.indexOf(":", mIndex);
      int quote1 = extracted.indexOf("\"", colon);
      int quote2 = extracted.indexOf("\"", quote1 + 1);
      if (quote1 != -1 && quote2 != -1)
      {
        meal = extracted.substring(quote1 + 1, quote2);
      }
    }

    // Send data to computer
    sendToComputer(food, price, meal);
  }
  else
  {
    Serial.print("Cohere request failed: ");
    Serial.println(httpResponseCode);
    displayText("Cohere err:\n" + String(httpResponseCode));
  }

  http.end();
}

void startTwoSegmentRecording()
{
  displayText("Listening...");

  // 第一段錄製 + 上傳
  Serial.println("錄製第1段音訊...");
  recordAudio();
  Serial.println("上傳第1段音訊至Deepgram...");
  transcript1 = sendToDeepgram();

  Serial.print("可用記憶體 (中間): ");
  Serial.println(ESP.getFreeHeap());

  // 第二段錄製 + 上傳
  Serial.println("錄製第2段音訊...");
  recordAudio();
  Serial.println("上傳第2段音訊至Deepgram...");
  transcript2 = sendToDeepgram();

  // 合併顯示
  String combined = transcript1 + " " + transcript2;
  displayText("Done.\nAsking Cohere...");

  // 將 combined 傳給 Cohere解析
  sendToCohere(combined);
}

void setup()
{
  Serial.begin(115200);
  Serial.println("連線中...");

  setupDisplay();

  WiFi.begin(ssid, password);
  Serial.print("連接WiFi");
  while (WiFi.status() != WL_CONNECTED)
  {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi已連線!");

  displayText("WiFi Connected!");

  setupI2S();
}

void loop()
{
  int32_t sample = readI2SSample();
  int32_t val = abs(sample >> 16);

  unsigned long currentTime = millis();

  if (val > CLAP_THRESHOLD && (currentTime - lastClapTime > CLAP_COOLDOWN))
  {
    lastClapTime = currentTime;
    if (!waitingForSecondClap)
    {
      firstClapTime = currentTime;
      waitingForSecondClap = true;
      Serial.println("First clap detected, waiting for second...");
      displayText("First clap!\nWait second...");
    }
    else
    {
      unsigned long delta = currentTime - firstClapTime;
      if (delta < 1000)
      {
        Serial.println("Double clap detected!");
        displayText("Double clap!");
        waitingForSecondClap = false;

        // 開始錄音兩段
        startTwoSegmentRecording();
      }
      else
      {
        firstClapTime = currentTime;
        Serial.println("Time exceeded, reset to first clap.");
        displayText("Time exceeded,\nreset clap");
      }
    }
  }

  if (waitingForSecondClap && (currentTime - firstClapTime > 1000))
  {
    waitingForSecondClap = false;
    Serial.println("Waiting for second clap timed out.");
    displayText("Second clap\ntimeout");
  }
}