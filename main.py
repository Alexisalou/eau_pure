import time
from datetime import datetime
import re
from rain_sensor import RainSensor
from interf import Envois_mesures, PLUVIOMETER_SENSOR_ID, LIMNIMETER_SENSOR_ID
from limnimètre import WaterLevelSensor
import pyfiglet

# Couleurs ANSI
GREEN = "\033[92m"
RED = "\033[91m"
CYAN = "\033[96m"
BOLD = "\033[1m"
RESET = "\033[0m"

def get_console_width(default=80):
    """Essaie de détecter la largeur du terminal."""
    try:
        import shutil
        width = shutil.get_terminal_size().columns
        return width
    except Exception:
        return default

def strip_ansi(s):
    """Supprime les codes ANSI d'une chaîne."""
    return re.sub(r'\x1B\[[0-?]*[ -/]*[@-~]', '', s)

def ansi_center(s, width):
    """Centre une chaîne (même colorée) dans une largeur donnée."""
    visible_len = len(strip_ansi(s))
    padding = max(0, width - visible_len)
    left = padding // 2
    right = padding - left
    return ' ' * left + s + ' ' * right

def print_station_title():
    width = get_console_width()
    # Titre principal en vert
    title1 = pyfiglet.figlet_format("Station hydrologique", font="slant")
    # Sous-titre en cyan
    title2 = pyfiglet.figlet_format("Projet Eau Pure", font="slant")
    # Centrage ligne par ligne
    for line in title1.rstrip().splitlines():
        print(ansi_center(GREEN + line + RESET, width))
    for line in title2.rstrip().splitlines():
        print(ansi_center(CYAN + line + RESET, width))

def animated_detection_banner(step=0, width=69):
    deco1 = ">" * (step % 6)
    deco2 = "<" * (step % 6)
    phrase = "⟪  DÉTECTION DES CAPTEURS EN COURS  ⟫"
    pad = (width - len(phrase) - 2*len(deco1)) // 2
    print(CYAN + deco1 + " " * pad + phrase + " " * pad + deco2 + RESET)

def loading_bar(progress, width=30):
    percent = int(progress * 100)
    bar_length = int(progress * width)
    bar = GREEN + "█" * bar_length + RESET + "-" * (width - bar_length)
    return f"[{bar}] {percent:3d}%"

def print_measure_table(pluie, profondeur):
    colw = 22
    print(CYAN + "\n┌" + "─" * colw + "┬" + "─" * colw + "┐")
    print("│" + " PLUVIOMÈTRE ".center(colw) + "│" + " LIMNIMÈTRE ".center(colw) + "│")
    print("├" + "─" * colw + "┼" + "─" * colw + "┤")
    pluie_str = ansi_center(GREEN + f"{pluie:.2f} L/m²" + RESET, colw)
    prof_str = ansi_center(GREEN + f"{profondeur:.2f} m" + RESET, colw)
    print("│" + pluie_str + "│" + prof_str + "│")
    print("└" + "─" * colw + "┴" + "─" * colw + "┘" + RESET)

def print_measure_table_error(pluie, erreur):
    colw = 22
    print(CYAN + "\n┌" + "─" * colw + "┬" + "─" * colw + "┐")
    print("│" + " PLUVIOMÈTRE ".center(colw) + "│" + " LIMNIMÈTRE ".center(colw) + "│")
    print("├" + "─" * colw + "┼" + "─" * colw + "┤")
    pluie_str = ansi_center(GREEN + f"{pluie:.2f} L/m²" + RESET, colw)
    err_str = ansi_center(BOLD + RED + erreur + RESET, colw)
    print("│" + pluie_str + "│" + err_str + "│")
    print("└" + "─" * colw + "┴" + "─" * colw + "┘" + RESET)

def main():
    IMPULSE_TO_RAIN_MM = 0.35
    rain_sensor = RainSensor(pin=17, impulse_to_rain_mm=IMPULSE_TO_RAIN_MM)
    water_level_sensor = WaterLevelSensor(pin=18, adc_channel=0)

    try:
        anim_step = 0
        while True:
            print('\033[2J\033[H', end='')  # Clear screen
            print_station_title()
            animated_detection_banner(anim_step)
            start = time.time()
            duration = 60  # Durée du cycle (60 secondes)

            while True:
                elapsed = time.time() - start
                progress = min(elapsed / duration, 1.0)
                bar = loading_bar(progress)
                print(f"\rProgression : {bar}  ", end='', flush=True)
                rain_sensor.update()
                
                if elapsed >= duration:
                    break

            print()  # Retour à la ligne après la barre

            # Mesure pluie
            current_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            rainfall = rain_sensor.get_rainfall()
            Envois_mesures(PLUVIOMETER_SENSOR_ID, rainfall, 'L/m²', current_time)
            print(f"{GREEN}Pluie envoyée : {rainfall:.2f} L/m² à {current_time}{RESET}")
            rain_sensor.reset()

            # Mesure limnimètre
            raw_value, voltage = water_level_sensor.read_value()
            profondeur_envoyee = 0.0
            limnimetre_error = None
            if voltage is not None:
                current = water_level_sensor.calculate_current(voltage)
                depth = water_level_sensor.calculate_depth(current)
                if 4 < current < 4.3:
                    profondeur_envoyee = 0.0
                    Envois_mesures(LIMNIMETER_SENSOR_ID, profondeur_envoyee, 'm', current_time)
                    print(f"{GREEN}Profondeur envoyée : {profondeur_envoyee:.2f} m (V={voltage}V, I=4 mA){RESET}")
                    limnimetre_error = "Hors de l'eau !"
                    print(f"{BOLD}{RED}Le capteur limnimètre n'est pas dans l'eau. Courant détecté : 4 mA{RESET}")
                elif current > 4.3:
                    voltage = round(voltage, 2)
                    current = round(current, 2)
                    profondeur_envoyee = round(depth, 2)
                    Envois_mesures(LIMNIMETER_SENSOR_ID, profondeur_envoyee, 'm', current_time)
                    print(f"{GREEN}Profondeur envoyée : {profondeur_envoyee:.2f} m (V={voltage}V, I={current} mA){RESET}")
                elif current < 3:
                    limnimetre_error = "Débranché !"
                    print(f"{BOLD}{RED}Erreur : Le capteur limnimètre est débranché ! Intensité trop faible{RESET}")
            else:
                limnimetre_error = "Lecture err."
                print(f"{BOLD}{RED}Erreur de lecture du capteur de niveau d'eau.{RESET}")

            if limnimetre_error:
                print_measure_table_error(rainfall, limnimetre_error)
            else:
                print_measure_table(rainfall, profondeur_envoyee)

            anim_step += 1
            time.sleep(2)

    except KeyboardInterrupt:
        print("Arrêt du programme.")
    finally:
        rain_sensor.cleanup()
        water_level_sensor.cleanup()

if __name__ == "__main__":
    main()
