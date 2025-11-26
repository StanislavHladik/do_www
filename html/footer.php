<footer class="site-footer">
    <!-- Control Panel -->
    <div class="control-panel" style="margin: 20px 0; padding: 15px; background-color: #f5f5f5; border-radius: 5px; border-top: 2px solid #ccc;">
        <h4 style="margin: 0 0 15px 0; color: #333;">Ovládací panel</h4>
        <div class="button-group" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="sendDetectionCommand('take_photo')" class="control-btn" style="padding: 8px 16px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 42px;">
                Pořiď snímek
            </button> 
            <button onclick="sendDetectionCommand('save_photo')" class="control-btn" style="padding: 8px 16px; background-color: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 42px;">
                Ulož snímek
            </button> 
            <!--        
            <button onclick="sendDetectionCommand('start')" class="control-btn" style="padding: 8px 16px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                ▶ Start Detection
            </button> 
            <button onclick="sendDetectionCommand('stop')" class="control-btn" style="padding: 8px 16px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                ⏹ Stop Detection
            </button>
            <button onclick="sendDetectionCommand('status')" class="control-btn" style="padding: 8px 16px; background-color: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                📊 Check Status
            </button>
            <button onclick="sendDetectionCommand('restart')" class="control-btn" style="padding: 8px 16px; background-color: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                🔄 Restart
            </button>
            -->
        </div>
        <div id="statusDisplay" style="margin-top: 10px; 
                                       padding: 8px; 
                                       background-color: white; 
                                       border-radius: 4px; 
                                       font-size: 13px; 
                                       min-height: 20px; 
                                       border-left: 16px solid #2196F3;
                                       color: black;">
            <strong>Status:</strong> <span id="statusText">Ready</span>
        </div>
    </div>

    <script>
        function sendDetectionCommand(command) {
            console.log(`Sending command: ${command}`);
            // Update status immediately
            document.getElementById('statusText').innerText = `Sending ${command} command...`;
            
            // Get machine number if available (from PHP variables)
            const cisloStroj = typeof window.cisloStroj !== 'undefined' ? window.cisloStroj : '1';
            
            // Send command to PHP backend
            fetch('detection_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    command: command,
                    cisloStroj: cisloStroj,
                    timestamp: Date.now()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('statusText').innerText = data.message;
                    // Change border color based on status
                    const statusDisplay = document.getElementById('statusDisplay');
                    if (command === 'start') {
                        statusDisplay.style.borderLeftColor = '#4CAF50';
                    } else if (command === 'take_photo') {
                        statusDisplay.style.borderLeftColor = '#4CAF50';
                    } else if (command === 'save_photo') {
                        statusDisplay.style.borderLeftColor = '#2196F3';
                    } else if (command === 'stop') {
                        statusDisplay.style.borderLeftColor = '#f44336';
                    } else {
                        statusDisplay.style.borderLeftColor = '#2196F3';
                    }
                } else {
                    document.getElementById('statusText').innerText = `Error: ${data.message}`;
                    document.getElementById('statusDisplay').style.borderLeftColor = '#f44336';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('statusText').innerText = `Connection error: ${error.message}`;
                document.getElementById('statusDisplay').style.borderLeftColor = '#f44336';
            });
        }

        // Add hover effects to buttons
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.control-btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.opacity = '0.8';
                    this.style.transform = 'translateY(-1px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.opacity = '1';
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</footer>
</body>

</html>