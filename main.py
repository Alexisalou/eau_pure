import sys
from interf_GSM import verifier_et_alerter as verifier_seuils, HuaweiApi
from PyQt6.QtWidgets import (
    QApplication, QWidget, QVBoxLayout, QLabel, QProgressBar, QGraphicsDropShadowEffect
)
from PyQt6.QtCore import Qt, QTimer
from PyQt6.QtGui import QFont, QColor, QPixmap


# Configuration de la base de données
HOTE_BDD = '10.0.200.14'
NOM_BDD = 'eau_pure'
UTILISATEUR_BDD = 'root'
MOT_DE_PASSE_BDD = 'ieufdl'
PORT_BDD = '9999'

config_bdd = {
    'host': HOTE_BDD,
    'user': UTILISATEUR_BDD,
    'password': MOT_DE_PASSE_BDD,
    'database': NOM_BDD,
    'port': PORT_BDD,
}


class EcranChargement(QWidget):
    def __init__(self, fonction_verification):
        super().__init__()
        self.fonction_verification = fonction_verification
        self.initialiser_interface()

    def initialiser_interface(self):
        self.setWindowTitle("Technicien du SBEP - Eau Pure")
        self.setFixedSize(700, 500)
        self.setStyleSheet("background-color: #001F4D;")

        layout = QVBoxLayout()
        layout.setContentsMargins(50, 40, 50, 40)
        layout.setSpacing(20)

        # === Logo ===
        logo = QLabel(self)
        pixmap = QPixmap("logo.png")
        pixmap = pixmap.scaledToHeight(150, Qt.TransformationMode.SmoothTransformation)
        logo.setPixmap(pixmap)
        logo.setAlignment(Qt.AlignmentFlag.AlignCenter)

        # Ombre douce autour du logo
        ombre_logo = QGraphicsDropShadowEffect()
        ombre_logo.setBlurRadius(25)
        ombre_logo.setOffset(0, 0)
        ombre_logo.setColor(QColor(0, 180, 255))
        logo.setGraphicsEffect(ombre_logo)

        layout.addWidget(logo)

        # === Titre ===
        titre = QLabel("TECHNICIEN DU SBEP")
        titre.setAlignment(Qt.AlignmentFlag.AlignCenter)
        titre.setFont(QFont("Segoe UI", 32, QFont.Weight.Bold))
        titre.setStyleSheet("color: white; letter-spacing: 2px;")

        ombre_texte = QGraphicsDropShadowEffect()
        ombre_texte.setBlurRadius(20)
        ombre_texte.setOffset(0, 0)
        ombre_texte.setColor(QColor(0, 200, 255))
        titre.setGraphicsEffect(ombre_texte)

        layout.addWidget(titre)

        # === Sous-titre animé ===
        self.sous_titre = QLabel("Chargement en cours")
        self.sous_titre.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self.sous_titre.setFont(QFont("Segoe UI", 14))
        self.sous_titre.setStyleSheet("color: #cceeff;")
        layout.addWidget(self.sous_titre)

        # === Barre de progression ===
        self.barre_progression = QProgressBar()
        self.barre_progression.setMaximum(6000)
        self.barre_progression.setTextVisible(False)
        self.barre_progression.setFixedHeight(30)
        self.barre_progression.setStyleSheet("""
            QProgressBar {
                background-color: #003366;
                border-radius: 15px;
                border: 2px solid #00aaff;
            }
            QProgressBar::chunk {
                background: QLinearGradient(
                    x1: 0, y1: 0, x2: 1, y2: 0,
                    stop: 0 #00c6ff, stop: 1 #007acc
                );
                border-radius: 15px;
            }
        """)
        layout.addWidget(self.barre_progression)

        self.setLayout(layout)

        # === Timers ===
        self.temps_ecoule = 0
        self.timer = QTimer()
        self.timer.timeout.connect(self.mettre_a_jour_progression)
        self.timer.start(50)

        self.bulles = ["Chargement en cours", "Chargement en cours.", "Chargement en cours..", "Chargement en cours..."]
        self.index_bulle = 0
        self.timer_texte = QTimer()
        self.timer_texte.timeout.connect(self.animer_texte)
        self.timer_texte.start(500)

    def animer_texte(self):
        self.sous_titre.setText(self.bulles[self.index_bulle])
        self.index_bulle = (self.index_bulle + 1) % len(self.bulles)

    def mettre_a_jour_progression(self):
        self.temps_ecoule += 50
        if self.temps_ecoule >= 6000:
            self.temps_ecoule = 6000
            self.barre_progression.setValue(self.temps_ecoule)
            self.timer.stop()
            self.fonction_verification()
        else:
            self.barre_progression.setValue(self.temps_ecoule)


def demarrer_application():
    app = QApplication(sys.argv)

    def lancer_verification():
        print("⏳ Vérification des seuils...")
        try:
            with HuaweiApi() as modem:
                verifier_seuils(config_bdd, modem)
            print("✅ Vérification terminée.\n")
        except Exception as erreur:
            print(f"❌ Erreur pendant la vérification : {erreur}\n")

        fenetre.temps_ecoule = 0
        fenetre.barre_progression.setValue(0)
        fenetre.timer.start(50)

    global fenetre
    fenetre = EcranChargement(lancer_verification)
    fenetre.show()
    sys.exit(app.exec())


if __name__ == "__main__":
    demarrer_application()
