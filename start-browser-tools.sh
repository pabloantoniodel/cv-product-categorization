#!/bin/bash
# Arranca servidor BrowserTools y MCP
cd ~/back-minerva/browser-tools-mcp
npx @agentdeskai/browser-tools-server@1.2.0 &
sleep 2
npx @agentdeskai/browser-tools-mcp@1.2.0 &
