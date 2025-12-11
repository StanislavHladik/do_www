# Navigační Systém Detekce Obrazu

## Přehled

Vytvořený navigační systém umožňuje snadnou navigaci mezi různými pracovními stanicemi a jejich funkcemi.

## Struktura

### 1. Hlavní stránka - `index.php`
**URL:** `http://your-server/index.php`

Automaticky skenuje adresář `/home/yolo` a nalezne všechny stroje podle vzoru `st{cislo}_*`:
- `st1_operky` → Stroj č. 1
- `st2_plasty` → Stroj č. 2  
- `st100_test` → Stroj č. 100
- `st99_trenink` → Stroj č. 99

Pro každý stroj zobrazuje:
- Číslo stroje
- Název stroje
- Stav konfigurace detekce
- Tlačítka pro:
  - **Náhledy** - zobrazení zachycených obrázků
  - **Výběr modelů** - správa AI modelů
  - **Trénink modelu** - trénování nových modelů

### 2. Stránka Náhledů - `nahledy.php`
**URL:** `nahledy.php?cisloStroj=2&nazevStroj=test_2&popisStroj=popis_test`

**Parametry:**
- `cisloStroj` (povinný) - číslo stroje (např. 2)
- `nazevStroj` (volitelný) - název stroje (např. "test_2")
- `popisStroj` (volitelný) - popis stroje (např. "popis_test")

Zobrazuje galerii zachycených obrázků pro vybraný stroj.

### 3. Stránka Výběru Modelů - `models_offer.php`
**URL:** `models_offer.php?cisloStroj=2&nazevStroj=test_2&popisStroj=popis_test`

**Parametry:** stejné jako u `nahledy.php`

Umožňuje:
- Procházet dostupné .pt modely
- Vybrat aktivní model pro detekci
- Restartovat službu detekce
- Zobrazit detaily modelů

### 4. Stránka Tréninku - `train.php` (NOVÁ)
**URL:** `train.php?cisloStroj=2&nazevStroj=test_2&popisStroj=popis_test`

**Parametry:** stejné jako u ostatních stránek

Funkce (připraveno pro implementaci):
- Výběr datasetu pro trénink
- Konfigurace parametrů tréninku:
  - Počet epoch
  - Velikost batch
  - Velikost obrázků
  - Typ YOLOv8 modelu (nano/small/medium/large/xlarge)
  - Název výstupního modelu
- Monitoring průběhu tréninku
- Zastavení probíhajícího tréninku

## Navigace

### Hlavní navigační lišta (v header.php)
Všechny stránky kromě `index.php` obsahují navigační lištu s tlačítky:
1. **Všechny stroje** - návrat na hlavní stránku
2. **Náhledy** - galerie obrázků
3. **Výběr modelů** - správa AI modelů
4. **Trénink** - trénování modelů

## Použití

### Příklad 1: Přímý odkaz na stroj č. 2
```
http://your-server/nahledy.php?cisloStroj=2&nazevStroj=plasty&popisStroj=ST2_Plasty
```

### Příklad 2: Minimální parametry (použijí se defaultní hodnoty)
```
http://your-server/nahledy.php?cisloStroj=2
```

### Příklad 3: Odkaz z hlavní stránky
```
http://your-server/index.php
```
Vyberte stroj z grafického přehledu.

## Automatická Detekce Strojů

Systém automaticky detekuje stroje podle:
1. Názvu složky v `/home/yolo/` odpovídající vzoru `st{cislo}_*`
2. Existence podsložky `Detekce_Obrazu` (indikuje konfigurovaný stroj)

### Speciální Stroje

Systém rozlišuje dva typy pracovišť:

**Produkční Stroje** (st1, st2, st3, ...):
- Běžné pracovní stanice pro detekci v produkci
- Zobrazeny v první sekci s modrým designem
- Ikona: ozubené kolo

**Speciální Pracovní Prostory**:
- **ST99 (Trénink)**: 
  - Určeno výhradně pro trénování modelů
  - Zlatý/oranžový design s označením "TRÉNINK"
  - Ikona: absolventská čepice
  - **Tlačítko:** Pouze "Zahájit Trénink Modelu" (zvýrazněné)
  
- **ST100 (Test)**:
  - Testovací prostředí pro vývoj
  - Fialový design s označením "TEST"
  - Ikona: zkumavka
  - **Tlačítka:** "Testovací Náhledy", "Testovací Modely", "Test Tréninku"

Speciální stroje jsou odděleny vlastní sekcí a mají odlišný vizuální design i funkce tlačítek pro snadné rozlišení účelu.

## Design

Všechny stránky sdílejí:
- Jednotný vzhled a barvy
- Responzivní design pro mobily a tablety
- Font Awesome ikony
- Gradient tlačítka s hover efekty
- Moderní karty s shadow efekty

## Budoucí Rozšíření

### Pro train.php:
1. Backend API pro spuštění tréninku
2. WebSocket pro real-time monitoring
3. Vizualizace metrik tréninku (loss, precision, recall)
4. Export výsledků tréninku
5. Automatické nahrání nového modelu po dokončení

### Obecné:
1. Autentizace uživatelů
2. Logging změn
3. Notifikace při dokončení tréninku
4. API pro vzdálené ovládání
5. Databáze pro historii operací

## Technické Detaily

- **PHP verze:** 7.4+
- **Závislosti:** Font Awesome 4.7, jQuery 3.7.1, html2canvas 1.4.1
- **CSS Framework:** Custom (gradient buttons, flexbox grid)
- **JavaScript:** Vanilla JS + jQuery

## Soubory

```
/var/www/html/
├── index.php              # Hlavní přehled strojů
├── nahledy.php           # Galerie obrázků
├── models_offer.php      # Správa modelů
├── train.php             # Trénink modelů (NOVÁ)
├── header.php            # Společná hlavička (AKTUALIZOVÁNA)
├── footer.php            # Společná patička
└── css/
    ├── style.css         # Hlavní styly
    └── navigation.css    # Navigační styly (AKTUALIZOVÁNA)
```

## Poznámky

- Všechny parametry URL jsou escapovány pomocí `htmlspecialchars()` pro bezpečnost
- Defaultní hodnoty jsou nastaveny pro testovací účely
- Cesty k souborům jsou dynamicky generovány podle čísla stroje
- Responsive design funguje na zařízeních od 320px do 4K obrazovek
