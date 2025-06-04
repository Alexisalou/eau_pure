import pytest
from unittest.mock import patch, MagicMock
from datetime import datetime
import interf  # remplace par le nom de ton fichier si différent

class TestEnvoisMesures:

    @patch('interf.mysql.connector.connect')
    def test_insertion_valide(self, mock_connect):
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_connect.return_value = mock_conn
        mock_conn.cursor.return_value = mock_cursor
        
        interf.Envois_mesures(1, 12.5, "mm", datetime.now())

        mock_cursor.execute.assert_called_once()
        mock_conn.commit.assert_called_once()
        mock_cursor.close.assert_called_once()
        mock_conn.close.assert_called_once()

    @patch('interf.mysql.connector.connect')
    def test_erreur_insertion(self, mock_connect):
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_connect.return_value = mock_conn
        mock_conn.cursor.return_value = mock_cursor

        def raise_error(*args, **kwargs):
            raise Exception("Erreur insertion")

        mock_cursor.execute.side_effect = raise_error

        interf.Envois_mesures(1, 12.5, "mm", datetime.now())

        mock_conn.commit.assert_not_called()
        mock_cursor.close.assert_called_once()
        mock_conn.close.assert_called_once()

    @patch('interf.mysql.connector.connect')
    def test_erreur_connexion_bdd(self, mock_connect):
        # Simuler erreur lors de la connexion à la BDD
        mock_connect.side_effect = Exception("Connexion échouée")

        # S'assurer que la fonction ne plante pas (gère l'exception)
        interf.Envois_mesures(1, 12.5, "mm", datetime.now())
