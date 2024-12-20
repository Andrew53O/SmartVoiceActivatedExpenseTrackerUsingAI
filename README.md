# Voice Based Accounting System

## How to Use It on Your Local Computer

### Setup

1. **Disable Firewall Temporarily:**
   - Temporarily disable your firewall to allow the ESP32 to access the server. Remember to re-enable it after setup.

2. **Find Your Computer's IP Address:**
   - Open PowerShell and run:
     ```powershell
     ipconfig | Select-String -Pattern "^\s*IPv4 Address\."
     ```
     - Example Output:
       ```
       IPv4 Address. . . . . . . . . . . : 192.168.0.102
       ```
   - Note down the IP address.

3. **Configure `config.h`:**
   - Create `config.h` from the template:
     ```bash
     cp config.example.h config.h
     ```
   - Open `config.h`  and replace the placeholders with your actual server URL and API keys:
     ```cpp
     // config.h
     #ifndef CONFIG_H
     #define CONFIG_H

     // Server URL
     const char* server_url = "http://192.168.0.102:8888/web/upload_data.php"; // Replace with your actual server URL

     // API Keys
     const char* deepgram_api_key = "YOUR_DEEPGRAM_API_KEY"; // Replace with your Deepgram API key
     const char* cohere_api_key = "YOUR_COHERE_API_KEY";     // Replace with your Cohere API key

     #endif
     ```

4. **Connect ESP32 and Computer to the Same Network:**
   - Ensure both devices are connected to the same local network.

5. **Start Your Web Server:**
   - Open XAMPP and start Apache and MySQL.
   - Ensure your database is connected properly.

6. **Upload and Run the Arduino Code:**
   - Open `voice_based_accounting_system`.ino in your Arduino IDE.
   - Verify and upload the code to your ESP32.

### Troubleshooting

- **Display Issues:**
  - If you encounter problems displaying data, ensure your `displayText` function handles line breaks and text formatting correctly.

- **HTTP Errors:**
  - Check your server URL and ensure the server is accessible from the ESP32.
  - Verify API keys are correct and have the necessary permissions.
