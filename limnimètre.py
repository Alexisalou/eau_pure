import spidev
import time
from sensor import Sensor
import RPi.GPIO as GPIO

# Classe pour gérer le convertisseur analogique/numérique MCP3208 via SPI
class MCP3208:
    def __init__(self, bus=0, device=0):
        # Initialisation de l'interface SPI
        self.spi = spidev.SpiDev()
        self.spi.open(bus, device)  # Ouvre la connexion SPI sur le bus et le device choisis
        self.spi.max_speed_hz = 1000000  # Définit la vitesse de communication SPI à 1 MHz

    def read_channel(self, channel):
        """
        Lit la valeur brute du canal ADC spécifié.
        Args:
            channel (int): numéro du canal à lire (0-7).
        Returns:
            int: valeur lue (0-4095) ou None si erreur.
        """
        try:
            # Envoie une requête SPI pour lire le canal voulu
            adc = self.spi.xfer2([1, (8 + channel) << 4, 0])
            # Combine les données reçues pour obtenir la valeur sur 12 bits
            data = ((adc[1] & 3) << 8) + adc[2]
            return data
        except Exception as e:
            print(f"Erreur de lecture du canal {channel} : {e}")
            return None

    def close(self):
        # Ferme proprement la connexion SPI
        self.spi.close()

# Classe pour gérer un capteur de niveau d'eau connecté via l'ADC
class WaterLevelSensor(Sensor):
    def __init__(
        self,
        pin,
        adc_channel,
        v_ref=3.3,
        correction_factor=1.954 / 0.488,
        resistance=250,
        min_current=4,
        max_current=20,
        max_depth=5,
        pull_up_down=GPIO.PUD_UP
    ):
        """
        Initialise le capteur de niveau d'eau.
        Args:
            pin (int): broche GPIO utilisée pour le capteur.
            adc_channel (int): canal ADC utilisé sur le MCP3208.
            v_ref (float): tension de référence de l'ADC.
            correction_factor (float): facteur de correction pour le capteur.
            resistance (float): valeur de la résistance (en ohms) dans le circuit.
            min_current (float): courant minimum du capteur (en mA, typiquement 4 mA).
            max_current (float): courant maximum du capteur (en mA, typiquement 20 mA).
            max_depth (float): profondeur maximale mesurable (en mètres).
            pull_up_down: mode de résistance interne GPIO.
        """
        super().__init__(pin, pull_up_down)
        self.adc = MCP3208()  # Initialise l'ADC
        self.adc_channel = adc_channel
        self.v_ref = v_ref
        self.correction_factor = correction_factor
        self.resistance = resistance
        self.min_current = min_current
        self.max_current = max_current
        self.max_depth = max_depth

    def read_value(self):
        """
        Lit la valeur brute du capteur et applique la conversion en tension corrigée.
        Returns:
            tuple: (valeur brute ADC, tension corrigée) ou (None, None) si erreur.
        """
        raw_value = self.adc.read_channel(self.adc_channel)
        if raw_value is None:
            return None, None
        voltage = (raw_value * self.v_ref) / 4096.0  # Conversion de la valeur brute en tension (V)
        corrected_voltage = voltage * self.correction_factor  # Application du facteur de correction
        return raw_value, corrected_voltage

    def calculate_current(self, voltage):
        """
        Calcule le courant traversant la résistance à partir de la tension mesurée.
        Args:
            voltage (float): tension corrigée (en volts).
        Returns:
            float: courant (en mA).
        """
        current = (voltage / self.resistance) * 1000  # Conversion en mA
        return current

    def calculate_depth(self, current):
        """
        Calcule la profondeur d'eau à partir du courant mesuré.
        Args:
            current (float): courant (en mA).
        Returns:
            float: profondeur (en mètres).
        """
        # Sature le courant dans la plage [min_current, max_current]
        if current < self.min_current:
            current = self.min_current
        elif current > self.max_current:
            current = self.max_current
        # Conversion linéaire du courant en profondeur
        depth = ((current - self.min_current) / (self.max_current - self.min_current)) * self.max_depth
        return depth

    def cleanup(self):
        """
        Nettoie les ressources utilisées (GPIO, SPI...).
        """
        super().cleanup()
        self.adc.close()
