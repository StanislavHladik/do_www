#!/bin/bash

# Test Training System Configuration
# This script checks if all required components are in place

echo "=== Training System Configuration Test ==="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check functions
check_exists() {
    if [ -e "$1" ]; then
        echo -e "${GREEN}✓${NC} $2"
        return 0
    else
        echo -e "${RED}✗${NC} $2"
        echo "  Path: $1"
        return 1
    fi
}

check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} $2"
        return 0
    else
        echo -e "${RED}✗${NC} $2"
        echo "  Path: $1"
        return 1
    fi
}

check_writable() {
    if [ -w "$1" ]; then
        echo -e "${GREEN}✓${NC} $2"
        return 0
    else
        echo -e "${YELLOW}⚠${NC} $2"
        echo "  Path: $1"
        return 1
    fi
}

echo "1. Training Directory Structure"
echo "--------------------------------"
check_dir "/home/yolo/st99_trenink/Detekce_Obrazu" "Training base directory exists"
check_exists "/home/yolo/st99_trenink/Detekce_Obrazu/trenink.py" "Training script exists"
check_writable "/home/yolo/st99_trenink/Detekce_Obrazu" "Training directory is writable"
echo ""

echo "2. Virtual Environment"
echo "----------------------"
check_dir "/home/yolo/st99_trenink/Detekce_Obrazu/Detekce_Obrazu_venv" "Virtual environment exists"
check_exists "/home/yolo/st99_trenink/Detekce_Obrazu/Detekce_Obrazu_venv/bin/python3" "Python binary exists in venv"
check_exists "/home/yolo/st99_trenink/Detekce_Obrazu/Detekce_Obrazu_venv/bin/activate" "Activate script exists"
echo ""

echo "3. Weights Directory"
echo "--------------------"
check_dir "/home/yolo/st99_trenink/Detekce_Obrazu/yolov5/weights" "Weights directory exists"

# Count .pt files
WEIGHT_COUNT=$(find /home/yolo/st99_trenink/Detekce_Obrazu/yolov5/weights -name "*.pt" 2>/dev/null | wc -l)
if [ "$WEIGHT_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓${NC} Found $WEIGHT_COUNT weight files (.pt)"
    find /home/yolo/st99_trenink/Detekce_Obrazu/yolov5/weights -name "*.pt" 2>/dev/null | head -5 | while read file; do
        echo "  - $(basename "$file")"
    done
else
    echo -e "${YELLOW}⚠${NC} No weight files found (.pt)"
    echo "  Weight files will be auto-downloaded when needed"
fi
echo ""

echo "4. Configuration File"
echo "---------------------"
if [ -f "/home/yolo/st99_trenink/Detekce_Obrazu/trenink.json" ]; then
    check_exists "/home/yolo/st99_trenink/Detekce_Obrazu/trenink.json" "Configuration file exists"
    check_writable "/home/yolo/st99_trenink/Detekce_Obrazu/trenink.json" "Configuration file is writable"
    echo ""
    echo "Current configuration:"
    cat /home/yolo/st99_trenink/Detekce_Obrazu/trenink.json
else
    echo -e "${YELLOW}⚠${NC} Configuration file doesn't exist yet"
    echo "  Will be created on first training submission"
fi
echo ""

echo "5. Web Files"
echo "------------"
check_exists "/var/www/html/train.php" "Main training page exists"
check_exists "/var/www/html/start_training.php" "Training endpoint exists"
check_exists "/var/www/html/load_train_config.php" "Config loader exists"
check_exists "/var/www/html/views/train_configuration_section.php" "Training form view exists"
check_exists "/var/www/html/get_weights.php" "Weights helper exists"
echo ""

echo "6. Log Directory"
echo "----------------"
check_writable "/tmp" "/tmp is writable (for logs)"
echo ""

# Check for existing training processes
echo "7. Running Processes"
echo "--------------------"
TRAINING_PROCS=$(ps aux | grep -E "trenink.py|train.py" | grep -v grep | wc -l)
if [ "$TRAINING_PROCS" -gt 0 ]; then
    echo -e "${YELLOW}⚠${NC} Found $TRAINING_PROCS running training process(es):"
    ps aux | grep -E "trenink.py|train.py" | grep -v grep
else
    echo -e "${GREEN}✓${NC} No training processes currently running"
fi
echo ""

# Check recent training logs
echo "8. Recent Training Logs"
echo "-----------------------"
RECENT_LOGS=$(find /tmp -name "training_output_*.log" -mtime -1 2>/dev/null | wc -l)
if [ "$RECENT_LOGS" -gt 0 ]; then
    echo -e "${GREEN}✓${NC} Found $RECENT_LOGS recent training log(s) (last 24h):"
    find /tmp -name "training_output_*.log" -mtime -1 -exec ls -lh {} \; | head -5
else
    echo -e "  No recent training logs found"
fi
echo ""

echo "=== Test Complete ==="
echo ""
echo "Summary:"
echo "--------"
echo "If all checks show ${GREEN}✓${NC}, the system is ready for training."
echo "Warnings (${YELLOW}⚠${NC}) are usually not critical."
echo "Errors (${RED}✗${NC}) need to be fixed before training can work."
echo ""
echo "To start training:"
echo "1. Visit http://your-server/train.php"
echo "2. Select a dataset and click 'Konfigurovat'"
echo "3. Configure parameters and click 'Zahájit Trénink'"
echo ""
echo "To monitor training:"
echo "  tail -f /tmp/training_output_*.log"
echo ""
