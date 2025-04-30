import time
from datetime import datetime, timedelta
from rain_sensor import RainSensor
from interf import Envois_mesures, PLUVIOMETER_SENSOR_ID, LIMNIMETER_SENSOR_ID, lire_seuils
from limnimètre import WaterLevelSensor
from api import send_sms

# Paramètres de connexion à la base (pour lire les seuils dynamiquement)
db_config = {
    'host': '10.0.14.4',
    'user': 'root',
    'password': 'ieufdl',
    'database': 'eau_pure',
    'port': '9999'
}

def main():
    IMPULSE_TO_RAIN_MM = 0.35
    rain_sensor = RainSensor(pin=17, impulse_to_rain_mm=IMPULSE_TO_RAIN_MM)
    water_level_sensor = WaterLevelSensor(pin=18, adc_channel=0)

    try:
        print("Détection des capteurs en cours...")

        while True:
            # Capteur de pluie
            rain_sensor.update()
            time.sleep(0.01)

            if datetime.now() - rain_sensor.last_impulse_time > timedelta(minutes=1):
                rainfall = rain_sensor.get_rainfall()
                current_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                Envois_mesures(PLUVIOMETER_SENSOR_ID, rainfall, 'L/m²', current_time)
                print(f"Pluie envoyée : {rainfall:.2f} L/m² à {current_time}")
                rain_sensor.reset()

                # Capteur de niveau d'eau
                raw_value, voltage = water_level_sensor.read_value()
                if voltage is not None:
                    current = water_level_sensor.calculate_current(voltage)
                    depth = water_level_sensor.calculate_depth(current)

                    voltage = round(voltage, 2)
                    current = round(current, 2)
                    depth = round(depth, 2)

                    Envois_mesures(LIMNIMETER_SENSOR_ID, depth, 'm', current_time)
                    print(f"Profondeur envoyée : {depth:.2f} m (V={voltage}V, I={current}mA)")
                else:
                    print("Erreur de lecture du capteur de niveau d'eau.")

                # Lire les seuils actuels depuis la base
                try:
                    seuil_pluie, seuil_niveau = lire_seuils(db_config)
                except Exception as e:
                    print(f"Erreur lors de la lecture des seuils : {e}")
                    seuil_pluie, seuil_niveau = None, None

                # Vérifier dépassement des seuils
                if seuil_pluie is not None and rainfall > seuil_pluie:
                    msg = f"ALERTE : Seuil pluviomètre dépassé ({round(rainfall,2)} L/m²). Prélèvement requis."
                    send_sms('+33643872007', msg)
                    print("SMS d'alerte pluviomètre envoyé.")

                if seuil_niveau is not None and depth > seuil_niveau:
                    msg = f"ALERTE : Seuil limnimètre dépassé ({round(depth,2)} m). Prélèvement requis."
                    send_sms('+33643872007', msg)
                    print("SMS d'alerte limnimètre envoyé.")

                # Message de confirmation
                send_sms('+33643872007', 'Les données des capteurs ont été envoyées !')
                time.sleep(60)

    except KeyboardInterrupt:
        print("Arrêt du programme.")
    finally:
        rain_sensor.cleanup()
        water_level_sensor.cleanup()

if __name__ == "__main__":
    main()
