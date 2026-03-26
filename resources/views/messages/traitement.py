"""Traitement d'image : histogramme d'amplitude et histogramme cumulatif.

Ce script charge une image (couleur ou niveaux de gris), calcule :
- l'histogramme d'amplitude (nombre de pixels par niveau de gris)
- l'histogramme cumulatif (CDF)

Il affiche aussi les deux graphiques côte à côte.

Usage :
    python traitement.py chemin/vers/image.jpg

Dépendances : opencv-python, numpy, matplotlib

Auteur: Copilot
"""

from __future__ import annotations

import sys
from pathlib import Path

import cv2
import matplotlib.pyplot as plt
import numpy as np


def compute_histogram(gray: np.ndarray, bins: int = 256) -> np.ndarray:
    """Calcule l'histogramme d'intensité d'une image en niveaux de gris.

    Args:
        gray: Image 2D (uint8).
        bins: Nombre de niveaux (par défaut 256 pour 0..255).

    Returns:
        Histogramme normalisé (somme = 1) de taille `bins`.
    """

    hist = cv2.calcHist([gray], [0], None, [bins], [0, 256])
    hist = hist.ravel().astype(np.float64)
    hist /= hist.sum()  # normalisation pour que la somme vaille 1
    return hist


def compute_cumulative_histogram(hist: np.ndarray) -> np.ndarray:
    """Calcule l'histogramme cumulatif (fonction de distribution cumulée)."""

    return np.cumsum(hist)


def plot_histograms(hist: np.ndarray, cumhist: np.ndarray, title_prefix: str = "") -> None:
    """Affiche l'histogramme et l'histogramme cumulatif."""

    bins = np.arange(len(hist))

    fig, axes = plt.subplots(1, 2, figsize=(12, 4))

    axes[0].bar(bins, hist, width=1.0, color="#2c7bb6")
    axes[0].set_title(f"{title_prefix}Histogramme d'amplitude")
    axes[0].set_xlabel("Niveau de gris")
    axes[0].set_ylabel("Probabilité")
    axes[0].set_xlim(0, len(hist) - 1)

    axes[1].plot(bins, cumhist, color="#d7191c")
    axes[1].set_title(f"{title_prefix}Histogramme cumulatif")
    axes[1].set_xlabel("Niveau de gris")
    axes[1].set_ylabel("Cumul")
    axes[1].set_xlim(0, len(hist) - 1)
    axes[1].set_ylim(0, 1)

    fig.tight_layout()
    plt.show()


def apply_open_close(gray: np.ndarray, kernel_size: int = 5) -> tuple[np.ndarray, np.ndarray]:
    """Applique une ouverture puis une fermeture sur une image en niveaux de gris.

    Args:
        gray: image 2D (uint8).
        kernel_size: taille du noyau de traitement (doit être impair).

    Returns:
        (opened, closed) images.
    """

    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (kernel_size, kernel_size))
    opened = cv2.morphologyEx(gray, cv2.MORPH_OPEN, kernel)
    closed = cv2.morphologyEx(gray, cv2.MORPH_CLOSE, kernel)
    return opened, closed


def binarize_manual(gray: np.ndarray, threshold: int = 127) -> np.ndarray:
    """Binarisation manuelle par seuil fixe."""

    _, binary = cv2.threshold(gray, threshold, 255, cv2.THRESH_BINARY)
    return binary


def binarize_otsu(gray: np.ndarray) -> tuple[np.ndarray, float]:
    """Binarisation automatique par méthode d'Otsu.

    Retourne l'image binaire et le seuil calculé.
    """

    thresh, binary = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    return binary, float(thresh)


def apply_convolution_filter(gray: np.ndarray, kernel: np.ndarray) -> np.ndarray:
    """Applique un filtre de convolution personnalisé à l'image."""

    return cv2.filter2D(gray, -1, kernel)


def compute_image_average(gray: np.ndarray) -> float:
    """Calcule la moyenne des valeurs de pixels de l'image en niveaux de gris."""

    return float(np.mean(gray))


def plot_convolution_result(original: np.ndarray, filtered: np.ndarray, title: str = "") -> None:
    """Affiche l'image originale et l'image filtrée côte à côte."""

    fig, axes = plt.subplots(1, 2, figsize=(10, 5))
    axes[0].imshow(original, cmap="gray", vmin=0, vmax=255)
    axes[0].set_title(f"{title}Original")
    axes[0].axis("off")

    axes[1].imshow(filtered, cmap="gray", vmin=0, vmax=255)
    axes[1].set_title(f"{title}Filtré (convolution)")
    axes[1].axis("off")

    fig.tight_layout()
    plt.show()


def detect_cavities(binary: np.ndarray) -> tuple[np.ndarray, int]:
    """Détecte les cavités (trous) dans une image binaire.

    Args:
        binary: Image binaire (uint8, valeurs 0/255).

    Returns:
        (image avec cavités surlignées, nombre de cavités détectées).
    """

    # Trouver les contours avec hiérarchie pour identifier les trous
    contours, hierarchy = cv2.findContours(binary, cv2.RETR_CCOMP, cv2.CHAIN_APPROX_SIMPLE)

    # Créer une copie pour surligner les cavités
    highlighted = cv2.cvtColor(binary, cv2.COLOR_GRAY2BGR) if len(binary.shape) == 2 else binary.copy()

    cavity_count = 0
    if hierarchy is not None:
        hierarchy = hierarchy[0]
        for i, h in enumerate(hierarchy):
            # Si c'est un trou (parent != -1 et child == -1)
            if h[2] == -1 and h[3] != -1:  # Pas d'enfant, mais parent existe
                cavity_count += 1
                # Dessiner le contour du trou en rouge
                cv2.drawContours(highlighted, contours, i, (0, 0, 255), 2)

    return highlighted, cavity_count


def plot_cavity_detection(binary: np.ndarray, highlighted: np.ndarray, cavity_count: int, title: str = "") -> None:
    """Affiche l'image binaire originale et celle avec cavités surlignées."""

    fig, axes = plt.subplots(1, 2, figsize=(12, 5))
    axes[0].imshow(binary, cmap="gray", vmin=0, vmax=255)
    axes[0].set_title(f"{title}Image binaire")
    axes[0].axis("off")

    axes[1].imshow(cv2.cvtColor(highlighted, cv2.COLOR_BGR2RGB))
    axes[1].set_title(f"{title}Cavités détectées ({cavity_count} trouvées)")
    axes[1].axis("off")

    fig.tight_layout()
    plt.show()


def plot_binary_and_hist(binary: np.ndarray, title: str = "") -> None:
    """Affiche une image binaire et son histogramme (0 / 255)."""

    counts = np.bincount(binary.ravel(), minlength=256)
    # On ne garde que 0 et 255
    counts = counts[[0, 255]]
    labels = [0, 255]

    fig, axes = plt.subplots(1, 2, figsize=(10, 4))
    axes[0].imshow(binary, cmap="gray", vmin=0, vmax=255)
    axes[0].set_title(f"{title}Image binaire")
    axes[0].axis("off")

    axes[1].bar(labels, counts, width=10, color="#4daf4a")
    axes[1].set_title(f"{title}Histogramme binaire")
    axes[1].set_xlabel("Valeur")
    axes[1].set_ylabel("Nombre de pixels")
    axes[1].set_xticks(labels)

    fig.tight_layout()
    plt.show()


def plot_open_close(original: np.ndarray, opened: np.ndarray, closed: np.ndarray) -> None:
    """Affiche l'image initiale, l'ouverture et la fermeture côte à côte."""

    fig, axes = plt.subplots(1, 3, figsize=(15, 5))
    axes[0].imshow(original, cmap="gray", vmin=0, vmax=255)
    axes[0].set_title("Original (gris)")
    axes[0].axis("off")

    axes[1].imshow(opened, cmap="gray", vmin=0, vmax=255)
    axes[1].set_title("Ouverture")
    axes[1].axis("off")

    axes[2].imshow(closed, cmap="gray", vmin=0, vmax=255)
    axes[2].set_title("Fermeture")
    axes[2].axis("off")

    fig.tight_layout()
    plt.show()


def main(image_path: Path) -> int:
    if not image_path.exists():
        print(f"Erreur : le fichier n'existe pas : {image_path}")
        return 1

    # Chargement (BGR) puis conversion en niveaux de gris
    img_bgr = cv2.imread(str(image_path), cv2.IMREAD_UNCHANGED)
    if img_bgr is None:
        print(f"Erreur : impossible de charger l'image : {image_path}")
        return 1

    # Si l'image est déjà en niveaux de gris, cv2.cvtColor n'est pas nécessaire
    if len(img_bgr.shape) == 2:
        gray = img_bgr
        title_prefix = "(Niveaux de gris) "
    else:
        gray = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2GRAY)
        title_prefix = "(Convertie en niveaux de gris) "

    # Calcul de la moyenne des pixels
    avg_value = compute_image_average(gray)
    print(f"Moyenne des pixels de l'image en gris : {avg_value:.2f}")

    # Application d'un filtre de convolution (ex. : moyenne 3x3)
    kernel_mean = np.ones((3, 3), np.float32) / 9  # Noyau de moyenne
    filtered = apply_convolution_filter(gray, kernel_mean)
    plot_convolution_result(gray, filtered, title="Filtre moyenne 3x3 - ")

    hist = compute_histogram(gray)
    cumhist = compute_cumulative_histogram(hist)

    plot_histograms(hist, cumhist, title_prefix=title_prefix)

    # Application d'une ouverture puis d'une fermeture morphologiques
    opened, closed = apply_open_close(gray, kernel_size=5)
    plot_open_close(gray, opened, closed)

    # Binarisation manuelle (seuil fixe) + affichage de l'histogramme binaire
    manual_threshold = 127
    binary_manual = binarize_manual(gray, manual_threshold)
    plot_binary_and_hist(binary_manual, title=f"Seuil {manual_threshold} - ")

    # Binarisation automatique (méthode d'Otsu) et histogramme
    binary_otsu, otsu_thresh = binarize_otsu(gray)
    print(f"Seuil calculé par Otsu : {otsu_thresh:.1f}")
    plot_binary_and_hist(binary_otsu, title=f"Otsu (t={otsu_thresh:.1f}) - ")

    # Détection de cavités sur l'image binaire Otsu
    highlighted, cavity_count = detect_cavities(binary_otsu)
    print(f"Nombre de cavités détectées : {cavity_count}")
    plot_cavity_detection(binary_otsu, highlighted, cavity_count, title="Otsu - ")

    return 0


if __name__ == "__main__":
    if len(sys.argv) == 2:
        input_path = Path(sys.argv[1])
    else:
        # Utilise ima1.png par défaut si aucun argument
        input_path = Path("ima1.png")
    sys.exit(main(input_path))
