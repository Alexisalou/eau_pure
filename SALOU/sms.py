import serial
import time

# Configuration du port série
ser = serial.Serial('/dev/ttyUSB0', 115200, timeout=5)

# Initialisation du modem GSM
ser.write(b'AT+CMGF=1\r')  # Mode texte
time.sleep(1)
ser.write(b'AT+CMGS="+33749719022"\r')  # Numéro de téléphone du destinataire
time.sleep(1)
ser.write(b'echantillon à récupérer\x1A')  # Message à envoyer suivi du caractère de fin de message (Ctrl+Z)
time.sleep(1)

# Lecture de la réponse du modem
response = ser.read(ser.inWaiting())
print(response.decode())

# Fermeture du port série
ser.close()
