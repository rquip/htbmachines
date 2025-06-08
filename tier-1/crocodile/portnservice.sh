#!/bin/bash

# Check if IP address is provided
if [ -z "$1" ]; then
    echo "Usage: $0 <ip_address>"
    exit 1
fi

IP=$1
LOG_FILE="port_scan_$(date +%Y%m%d_%H%M%S).log"

# Function to log messages with timestamp
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Step 1: Ping check
log "Starting scan for $IP"
log "Pinging target..."
if ! ping -c 3 "$IP" &> /dev/null; then
    log "ERROR: $IP is not responding to ping"
    exit 1
fi
log "Ping successful - host appears to be up"

# Step 2: Fast port scan to find all open ports
log "Starting initial port scan..."
OPEN_PORTS=$(nmap -Pn -T4 --min-rate 1000 --max-retries 1 -p- "$IP" | grep ^[0-9] | cut -d '/' -f 1 | tr '\n' ',' | sed 's/,$//')

if [ -z "$OPEN_PORTS" ]; then
    log "No open ports found"
    exit 0
fi

log "Found open ports: $OPEN_PORTS"

# Step 3: Detailed scan on open ports
log "Starting detailed scan on open ports..."
nmap -Pn -T4 -sV -sC -p "$OPEN_PORTS" "$IP" -oN "$LOG_FILE.detailed"

log "Scan completed successfully"
log "Results saved to:"
log "  - Quick scan: $LOG_FILE"
log "  - Detailed scan: $LOG_FILE.detailed"

# Combine logs
cat "$LOG_FILE.detailed" >> "$LOG_FILE"
rm "$LOG_FILE.detailed"

echo "Full results saved to $LOG_FILE"
