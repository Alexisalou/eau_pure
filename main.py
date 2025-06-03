import sys
from interf_GSM import verifier_et_alert, HuaweiApi
from PyQt6.QtWidgets import (
    QApplication, QWidget, QVBoxLayout, QLabel, QProgressBar, QGraphicsDropShadowEffect
)
from PyQt6.QtCore import Qt, QTimer
from PyQt6.QtGui import QFont, QColor

DATABASE_HOST = '10.0.14.4'
DATABASE_NAME = 'eau_pure'
DATABASE_USER = 'root'
DATABASE_PASSWORD = 'ieufdl'
DATABASE_PORT = '9999'

db_config = {
    'host': DATABASE_HOST,
    'user': DATABASE_USER,
    'password': DATABASE_PASSWORD,
    'database': DATABASE_NAME,
    'port': DATABASE_PORT,
}

class LoadingScreen(QWidget):
    def __init__(self, duration=60000, callback=None):
        super().__init__()
        self.duration = duration
        self.elapsed = 0
        self.callback = callback
        self.init_ui()

    def init_ui(self):
        self.setWindowTitle("Chargement - Technicien du SBEP")
        self.setFixedSize(600, 300)
        self.setStyleSheet("background-color: #001F4D;")

        layout = QVBoxLayout()
        layout.setContentsMargins(50, 50, 50, 50)

        label = QLabel("TECHNICIEN DU SBEP", self)
        label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        font = QFont("Segoe UI", 28, QFont.Weight.Bold)
        label.setFont(font)
        label.setStyleSheet("color: white;")

        shadow = QGraphicsDropShadowEffect()
        shadow.setBlurRadius(15)
        shadow.setOffset(4, 4)
        shadow.setColor(QColor(0, 150, 255))
        label.setGraphicsEffect(shadow)

        layout.addWidget(label)

        self.progress = QProgressBar(self)
        self.progress.setMaximum(self.duration)
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

        self.timer = QTimer()
        self.timer.timeout.connect(self.update_progress)
        self.timer.start(50)

    def update_progress(self):
        self.elapsed += 50
        self.progress.setValue(self.elapsed)
        if self.elapsed >= self.duration:
            self.timer.stop()
            self.close()
            if self.callback:
                self.callback()

def main_loop():
    app = QApplication(sys.argv)

    try:
        huawei_api = HuaweiApi()
    except Exception as e:
        print("Erreur de connexion au modem Huawei :", e)
        sys.exit(1)

    def cycle():
        loading_screen = LoadingScreen(duration=60000, callback=run_verification)
        loading_screen.show()

    def run_verification():
        print("⏳ Vérification des seuils...")
        try:
            verifier_et_alert(db_config, huawei_api)
            print("✅ Vérification terminée.")
        except Exception as e:
            print("❌ Erreur durant la vérification :", e)
        # Relancer le cycle dans 60 secondes
        QTimer.singleShot(60000, cycle)

    # Démarrer le 1er cycle immédiatement
    cycle()

    sys.exit(app.exec())

if __name__ == "__main__":
    main_loop()
