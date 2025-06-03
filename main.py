import sys
from interf_GSM import verifier_et_alert, HuaweiApi  # On importe notre logique métier GSM
from PyQt6.QtWidgets import (
    QApplication, QWidget, QVBoxLayout, QLabel, QProgressBar, QGraphicsDropShadowEffect
)
from PyQt6.QtCore import Qt, QTimer
from PyQt6.QtGui import QFont, QColor

# Configuration de la base de données
DATABASE_HOST = '10.0.14.4'
DATABASE_NAME = 'eau_pure'
DATABASE_USER = 'root'
DATABASE_PASSWORD = 'ieufdl'
DATABASE_PORT = '9999'

# On centralise la config dans un dictionnaire pour la passer facilement aux fonctions
db_config = {
    'host': DATABASE_HOST,
    'user': DATABASE_USER,
    'password': DATABASE_PASSWORD,
    'database': DATABASE_NAME,
    'port': DATABASE_PORT,
}

# Classe principale pour afficher l'écran de chargement avec une barre de progression
class LoadingScreen(QWidget):
    def __init__(self, verification_callback):
        super().__init__()
        self.verification_callback = verification_callback
        self.init_ui()

    # On définit ici l'interface utilisateur
    def init_ui(self):
        self.setWindowTitle("Technicien du SBEP - Surveillance")
        self.setFixedSize(600, 300)
        self.setStyleSheet("background-color: #001F4D;")  # Fond bleu foncé

        layout = QVBoxLayout()
        layout.setContentsMargins(50, 50, 50, 50)

        # Titre centré avec effet de lumière
        label = QLabel("TECHNICIEN DU SBEP", self)
        label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        font = QFont("Segoe UI", 28, QFont.Weight.Bold)
        label.setFont(font)
        label.setStyleSheet("color: white;")

        # Effet d'ombre bleu sous le texte
        shadow = QGraphicsDropShadowEffect()
        shadow.setBlurRadius(15)
        shadow.setOffset(4, 4)
        shadow.setColor(QColor(0, 150, 255))
        label.setGraphicsEffect(shadow)

        layout.addWidget(label)

        # Barre de progression personnalisée
        self.progress = QProgressBar(self)
        self.progress.setMaximum(6000)  # 6 secondes d’attente (6000 ms)
        self.progress.setTextVisible(True)
        self.progress.setStyleSheet("""
            QProgressBar {
                border: 2px solid #00aaff;
                border-radius: 15px;
                background-color: #003366;
                color: white;
                font: 14pt 'Segoe UI';
                text-align: center;
            }
            QProgressBar::chunk {
                background-color: qlineargradient(
                    x1:0, y1:0, x2:1, y2:0,
                    stop:0 #00c6ff, stop:1 #005a9c);
                border-radius: 15px;
            }
        """)
        layout.addWidget(self.progress)

        self.setLayout(layout)

        # On initialise le timer pour faire avancer la barre
        self.elapsed = 0
        self.timer_progress = QTimer()
        self.timer_progress.timeout.connect(self.update_progress)
        self.timer_progress.start(50)  # Incrémente toutes les 50ms

    # Cette fonction fait avancer la barre de progression petit à petit
    def update_progress(self):
        self.elapsed += 50
        if self.elapsed >= 6000:
            self.elapsed = 6000
            self.progress.setValue(self.elapsed)
            self.timer_progress.stop()
            self.verification_callback()  # Quand on atteint 100%, on lance la vérif
        else:
            self.progress.setValue(self.elapsed)

# Boucle principale de l'application
def main_loop():
    app = QApplication(sys.argv)

    # Fonction appelée à chaque fois que la barre atteint 100%
    def run_verification():
        print("⏳ Vérification des seuils...")
        try:
            with HuaweiApi() as huawei_api:
                verifier_et_alert(db_config, huawei_api)
            print("✅ Vérification terminée.\n")
        except Exception as e:
            print(f"❌ Erreur dans la vérification: {e}\n")

        # On redémarre la barre pour la prochaine vérif automatique
        loading_screen.elapsed = 0
        loading_screen.progress.setValue(0)
        loading_screen.timer_progress.start(50)

    # Création de l’écran de chargement avec la fonction de vérification
    loading_screen = LoadingScreen(run_verification)
    loading_screen.show()

    # Lancement de l’interface graphique Qt
    sys.exit(app.exec())

# Point d’entrée du script
if __name__ == "__main__":
    main_loop()
