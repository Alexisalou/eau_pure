import RPi.GPIO as GPIO

class Sensor:
    def __init__(self, pin, pull_up_down=GPIO.PUD_UP):
        """
        Initialise un capteur sur une broche GPIO donnée.

        Args:
            pin (int): Numéro de la broche GPIO (mode BCM).
            pull_up_down: Type de résistance interne (PULL UP par défaut).
        """
        self.pin = pin
        self.pull_up_down = pull_up_down
        self.previous_state = GPIO.LOW  # État précédent du capteur (basse par défaut)

        # Configuration du mode de numérotation des broches (BCM) et de la broche en entrée
        GPIO.setmode(GPIO.BCM)
        GPIO.setup(self.pin, GPIO.IN, pull_up_down=self.pull_up_down)

    def read_state(self):
        """
        Lit et retourne l'état actuel de la broche du capteur (HIGH ou LOW).
        """
        return GPIO.input(self.pin)

    def cleanup(self):
        """
        Libère toutes les ressources GPIO utilisées par le script.
        À appeler avant de quitter le programme pour éviter les conflits.
        """
        GPIO.cleanup()
