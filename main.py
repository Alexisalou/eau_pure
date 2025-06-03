import sys
from interf_GSM import verifier_et_alerter as verifier_seuils, HuaweiApi  # On importe la logique GSM
from PyQt6.QtWidgets import (
    QApplication, QWidget, QVBoxLayout, QLabel, QProgressBar, QGraphicsDropShadowEffect
)
from PyQt6.QtCore import Qt, QTimer
from PyQt6.QtGui import QFont, QColor

# Configuration de la base de données
HOTE_BDD = '10.0.14.4'
NOM_BDD = 'eau_pure'
UTILISATEUR_BDD = 'root'
MOT_DE_PASSE_BDD = 'ieufdl'
PORT_BDD = '9999'

# Regroupement dans un dictionnaire de configuration
config_bdd = {
    'host': HOTE_BDD,
    'user': UTILISATEUR_BDD,
    'password': MOT_DE_PASSE_BDD,
    'database': NOM_BDD,
    'port': PORT_BDD,
}

# Fenêtre de chargement avec barre de progression
class EcranChargement(QWidget):
    def __init__(self, fonction_verification):
        super().__init__()
        self.fonction_verification = fonction_verification
        self.initialiser_interface()

    def initialiser_interface(self):
        self.setWindowTitle("Technicien du SBEP - Surveillance")
        self.setFixedSize(600, 300)
        self.setStyleSheet("background-color: #001F4D;")

        disposition = QVBoxLayout()
        disposition.setContentsMargins(50, 50, 50, 50)

        # Titre
        titre = QLabel("TECHNICIEN DU SBEP", self)
        titre.setAlignment(Qt.AlignmentFlag.AlignCenter)
        police = QFont("Segoe UI", 28, QFont.Weight.Bold)
        titre.setFont(police)
        titre.setStyleSheet("color: white;")

        # Ombre sous le texte
        ombre = QGraphicsDropShadowEffect()
        ombre.setBlurRadius(15)
        ombre.setOffset(4, 4)
        ombre.setColor(QColor(0, 150, 255))
        titre.setGraphicsEffect(ombre)

        disposition.addWidget(titre)

        # Barre de progression
        self.barre_progression = QProgressBar(self)
        self.barre_progression.setMaximum(6000)
        self.barre_progression.setTextVisible(True)
        self.barre_progression.setStyleSheet("""
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
        disposition.addWidget(self.barre_progression)

        self.setLayout(disposition)

        # Timer pour avancer la barre
        self.temps_ecoule = 0
        self.timer = QTimer()
        self.timer.timeout.connect(self.mettre_a_jour_progression)
        self.timer.start(50)

    def mettre_a_jour_progression(self):
        self.temps_ecoule += 50
        if self.temps_ecoule >= 6000:
            self.temps_ecoule = 6000
            self.barre_progression.setValue(self.temps_ecoule)
            self.timer.stop()
            self.fonction_verification()
        else:
            self.barre_progression.setValue(self.temps_ecoule)

# Boucle principale
def demarrer_application():
    app = QApplication(sys.argv)

    # Fonction de vérification à lancer
    def lancer_verification():
        print("⏳ Vérification des seuils...")
        try:
            with HuaweiApi() as modem:
                verifier_seuils(config_bdd, modem)
            print("✅ Vérification terminée.\n")
        except Exception as erreur:
            print(f"❌ Erreur pendant la vérification : {erreur}\n")

        # Réinitialisation de l’écran
        fenetre.temps_ecoule = 0
        fenetre.barre_progression.setValue(0)
        fenetre.timer.start(50)

    fenetre = EcranChargement(lancer_verification)
    fenetre.show()

    sys.exit(app.exec())

# Point d'entrée
if __name__ == "__main__":
    demarrer_application()
