import time
from datetime import datetime, timedelta
from rain_sensor import RainSensor
from interf import Envois_mesures, PLUVIOMETER_SENSOR_ID

# Information de connexion à la BDD
DATABASE_HOST = '10.0.14.4'  
DATABASE_NAME = 'eau_pure'  
DATABASE_USER = 'root'  
DATABASE_PASSWORD = 'ieufdl'
DATABASE_PORT = '9999'  # Port mysql ouvert sur le serveur

def main():
    # Facteur de conversion pour le pluviomètre SEN0575
    IMPULSE_TO_RAIN_MM = 0.2794

    # Initialiser le capteur de pluie
    rain_sensor = RainSensor(pin=17, impulse_to_rain_mm=IMPULSE_TO_RAIN_MM)
    
    try:
        print("Détection de pluie en cours...")
        while True:
            rain_sensor.update()
            time.sleep(0.01)

            # Envoi des données à la base de données toutes les minutes
            if datetime.now() - rain_sensor.last_impulse_time > timedelta(minutes=1):
                total_rainfall_mm = rain_sensor.get_rainfall()
                current_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                Envois_mesures(PLUVIOMETER_SENSOR_ID, total_rainfall_mm, 'mm', current_time)
                print(f"Envoi des données: {total_rainfall_mm:.2f} mm à {current_time}")
                rain_sensor.reset()
                time.sleep(60)  # Attendre 1 minute avant de reprendre les mesures

    except KeyboardInterrupt:
        print("Arrêt du programme")
    finally:
        rain_sensor.cleanup()

if __name__ == "__main__":
    main()