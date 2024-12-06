import serial
import sounddevice as sd
import scipy.io.wavfile as wav
import requests
import json
import time

# Serial port for ESP32
ESP32_PORT = "COM3"  # Replace with your ESP32's port (e.g., "/dev/ttyUSB0" on Linux/Mac)
BAUD_RATE = 115200
DURATION = 5  # Record for 5 seconds after detecting two claps
OUTPUT_FILE = "recorded_audio.wav"

# Cohere API settings
COHERE_API_URL = "https://api.cohere.ai/generate"
COHERE_API_KEY = "0Khe705NuHP7naQApcEa018DDWM42ti7GQ8tfXsj"

def record_audio():
    """Record audio from the microphone."""
    print("Recording...")
    fs = 16000  # Sampling frequency
    audio = sd.rec(int(DURATION * fs), samplerate=fs, channels=1, dtype='int16')
    sd.wait()  # Wait for the recording to finish
    wav.write(OUTPUT_FILE, fs, audio)  # Save as WAV file
    print(f"Audio recorded and saved to {OUTPUT_FILE}")
    return OUTPUT_FILE

def send_to_llm(audio_text):
    """Send text to Cohere LLM for processing."""
    # Construct the structured prompt
    prompt = (
        "Extract the following details from the text:\n"
        "- What kind of food did the user eat?\n"
        "- What type of food is it (choose one: breakfast, lunch, dinner, snack)?\n"
        "- What is the cost in New Taiwan Dollars (NTD)?\n\n"
        "Return the result as:\n[Food, Type, Cost]\n\n"
        f"### Input:\n{audio_text}\n\n### Output:"
    )

    # Cohere API request
    headers = {
        "Authorization": f"Bearer {COHERE_API_KEY}",
        "Content-Type": "application/json"
    }
    data = {
        "model": "command-xlarge",
        "prompt": prompt,
        "max_tokens": 50,
        "temperature": 0.7
    }

    response = requests.post(COHERE_API_URL, headers=headers, json=data)
    if response.status_code == 200:
        result = response.json()
        return result.get("generations")[0].get("text", "").strip()
    else:
        print("Error:", response.status_code, response.text)
        return "Error processing request."

def listen_to_esp32():
    """Listen for the 'START_LISTENING' signal from the ESP32."""
    with serial.Serial(ESP32_PORT, BAUD_RATE, timeout=1) as ser:
        print("Listening for ESP32 signal...")
        while True:
            line = ser.readline().decode('utf-8').strip()
            if line == "START_LISTENING":
                print("Two claps detected. Recording audio...")
                audio_path = record_audio()
                # Convert audio to text (use a Speech-to-Text API or manual input)
                audio_text = input("Enter the simulated transcribed text: ")  # Replace with real transcription
                print("Sending text to LLM...")
                response = send_to_llm(audio_text)
                print("LLM Response:", response)

                # Send the response back to the ESP32
                ser.write((response + "\n").encode('utf-8'))
                time.sleep(1)  # Wait before listening again

if __name__ == "__main__":
    listen_to_esp32()
