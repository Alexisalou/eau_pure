import RPi.GPIO as GPIO
import time
from datetime import datetime, timedelta
from sensor import Sensor

class RainSensor(Sensor):
    def __init__(self, pin, impulse_to_rain_mm):
        super().__init__(pin)
        self.impulse_to_rain_mm = impulse_to_rain_mm
        self.rain_count = 0
        self.rainfall_mm = 0.0
        self.last_impulse_time = datetime.now()

    def update(self):
        # Lire l'état actuel de la broche
        current_state = self.read_state()

        # Vérifier si l'état est passé de haut à bas (impulsion détectée)
        if self.previous_state == GPIO.HIGH and current_state == GPIO.LOW:
            self.rain_count += 1
            self.rainfall_mm += self.impulse_to_rain_mm
            self.last_impulse_time = datetime.now()
            print(f"Impulsion détectée ! Total : {self.rain_count}, Pluie tombée : {self.rainfall_mm:.2f} mm")

        # Mettre à jour l'état précédent
        self.previous_state = current_state

    def reset(self):
        self.rain_count = 0
        self.rainfall_mm = 0.0

    def get_rainfall(self):
        return self.rainfall_mm

    def cleanup(self):
        GPIO.cleanup()