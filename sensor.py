import RPi.GPIO as GPIO

class Sensor:
    def __init__(self, pin, pull_up_down=GPIO.PUD_UP):
        self.pin = pin
        self.pull_up_down = pull_up_down
        self.previous_state = GPIO.LOW

        # Configuration GPIO
        GPIO.setmode(GPIO.BCM)
        GPIO.setup(self.pin, GPIO.IN, pull_up_down=self.pull_up_down)

    def read_state(self):
        return GPIO.input(self.pin)

    def cleanup(self):
        GPIO.cleanup()
