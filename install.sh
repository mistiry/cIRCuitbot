#!/bin/bash
# cIRCuitbot systemd service installer
set -e

if [ "$EUID" -ne 0 ]; then
    echo "This script must be run as root: sudo ./install.sh"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATE="$SCRIPT_DIR/install/circuitbot.service.template"

if [ ! -f "$TEMPLATE" ]; then
    echo "Error: Service template not found at $TEMPLATE"
    exit 1
fi

echo "cIRCuitbot Service Installer"
echo "============================"
echo ""
read -p "Bot name (used for the service name, e.g. myBot): " BOTNAME
read -p "System user to run the bot as: " BOTUSER
read -p "Full path to cIRCuitbot directory (containing bot.php): " WORKDIR
read -p "Full path to bot config file: " CONFFILE

if [ -z "$BOTNAME" ] || [ -z "$BOTUSER" ] || [ -z "$WORKDIR" ] || [ -z "$CONFFILE" ]; then
    echo "Error: All fields are required."
    exit 1
fi

if ! id "$BOTUSER" &>/dev/null; then
    echo "Error: User '$BOTUSER' does not exist."
    exit 1
fi

if [ ! -f "$WORKDIR/bot.php" ]; then
    echo "Error: bot.php not found in '$WORKDIR'."
    exit 1
fi

if [ ! -f "$CONFFILE" ]; then
    echo "Error: Config file not found at '$CONFFILE'."
    exit 1
fi

SERVICEFILE="/etc/systemd/system/circuitbot-${BOTNAME}.service"

sed \
    -e "s|__BOTNAME__|${BOTNAME}|g" \
    -e "s|__BOTUSER__|${BOTUSER}|g" \
    -e "s|__WORKDIR__|${WORKDIR}|g" \
    -e "s|__CONFFILE__|${CONFFILE}|g" \
    "$TEMPLATE" > "$SERVICEFILE"

chmod 644 "$SERVICEFILE"
systemctl daemon-reload
systemctl enable "circuitbot-${BOTNAME}"

echo ""
echo "Service installed: circuitbot-${BOTNAME}"
echo ""
echo "  Start:   systemctl start circuitbot-${BOTNAME}"
echo "  Stop:    systemctl stop circuitbot-${BOTNAME}"
echo "  Restart: systemctl restart circuitbot-${BOTNAME}"
echo "  Status:  systemctl status circuitbot-${BOTNAME}"
echo "  Logs:    journalctl -u circuitbot-${BOTNAME} -f"
