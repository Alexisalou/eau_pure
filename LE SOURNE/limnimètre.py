import spidev
import time
from sensor import Sensor
import RPi.GPIO as GPIO

class MCP3208:
    def __init__(self, bus=0, device=0):
        self.spi = spidev.SpiDev()
        self.spi.open(bus, device)
        self.spi.max_speed_hz = 1000000  # 1 MHz

    def read_channel(self, channel):
        try:
            adc = self.spi.xfer2([1, (8 + channel) << 4, 0])
            data = ((adc[1] & 3) << 8) + adc[2]
            return data
        except Exception as e:
            print(f"Erreur de lecture du canal {channel} : {e}")
            return None

    def close(self):
        self.spi.close()

class WaterLevelSensor(Sensor):
    def __init__(
        self,
        pin,
        adc_channel,
        v_ref=3.3,
        correction_factor=1.928 / 0.540,
        resistance=250,
        min_current=4,
        max_current=20,
        max_depth=5,
        pull_up_down=GPIO.PUD_UP
    ):
        super().__init__(pin, pull_up_down)
        self.adc = MCP3208()
        self.adc_channel = adc_channel
        self.v_ref = v_ref
        self.correction_factor = correction_factor
        self.resistance = resistance
        self.min_current = min_current
        self.max_current = max_current
        self.max_depth = max_depth

    def read_value(self):
        raw_value = self.adc.read_channel(self.adc_channel)
        if raw_value is None:
            return None, None
        voltage = (raw_value * self.v_ref) / 4096.0
        corrected_voltage = voltage * self.correction_factor
        return raw_value, corrected_voltage

    def calculate_current(self, voltage):
        current = (voltage / self.resistance) * 1000  # en mA
        return current

    def calculate_depth(self, current):
        if current < self.min_current:
            current = self.min_current
        elif current > self.max_current:
            current = self.max_current
        depth = ((current - self.min_current) / (self.max_current - self.min_current)) * self.max_depth
        return depth

    def cleanup(self):
        super().cleanup()
        self.adc.close()
