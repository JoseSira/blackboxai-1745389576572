<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Generación de Códigos QR</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Scripts necesarios -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // Función para mostrar alertas
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
            } text-white`;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.classList.add('opacity-0', 'transition-opacity');
                setTimeout(() => alertDiv.remove(), 300);
            }, 3000);
        }

        // Función para mostrar el estado de carga
        function showLoading(show = true) {
            let loadingEl = document.getElementById('loading-overlay');
            if (!loadingEl && show) {
                loadingEl = document.createElement('div');
                loadingEl.id = 'loading-overlay';
                loadingEl.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                loadingEl.innerHTML = `
                    <div class="bg-white p-6 rounded-lg shadow-xl text-center transform scale-100 animate-fadeIn">
                        <div class="relative">
                            <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-200 mx-auto mb-4"></div>
                            <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent absolute top-0 left-1/2 -translate-x-1/2"></div>
                        </div>
                        <p class="text-gray-700 text-lg font-semibold animate-pulse">Generando códigos QR...</p>
                        <p class="text-gray-500 text-sm mt-2">Por favor, espere un momento</p>
                    </div>
                `;
                document.body.appendChild(loadingEl);
            } else if (loadingEl && !show) {
                loadingEl.remove();
            }
        }

        // Función para verificar si la librería QRCode está cargada
        function checkQRCodeLibrary() {
            return new Promise((resolve, reject) => {
                let attempts = 0;
                const maxAttempts = 10;
                
                const check = () => {
                    if (typeof QRCode !== 'undefined') {
                        resolve();
                    } else if (attempts >= maxAttempts) {
                        reject(new Error('No se pudo cargar la librería QRCode'));
                    } else {
                        attempts++;
                        setTimeout(check, 200);
                    }
                };
                
                check();
            });
        }

        // Función para esperar a que el QR se genere
        function waitForQR(code) {
            return new Promise((resolve, reject) => {
                let attempts = 0;
                const maxAttempts = 10;
                
                const checkQR = () => {
                    const qrContainer = document.querySelector(`#qr-${code}`);
                    const canvas = qrContainer?.querySelector('canvas');
                    
                    if (canvas) {
                        resolve(qrContainer);
                    } else if (attempts >= maxAttempts) {
                        reject(new Error('Tiempo de espera agotado para generar el QR'));
                    } else {
                        attempts++;
                        setTimeout(checkQR, 200);
                    }
                };
                
                checkQR();
            });
        }

        // Función para inicializar los códigos QR
        async function initializeQRCodes() {
            await checkQRCodeLibrary();
            
            await Promise.all(window.QR_CONFIG.codes.map(code => new Promise((resolve, reject) => {
                const qrUrl = `${window.QR_CONFIG.baseUrl}/redemption.php?code=${code}`;
                try {
                    const qrContainer = document.getElementById(`qr-${code}`);
                    if (!qrContainer) {
                        reject(new Error(`Contenedor no encontrado para el código: ${code}`));
                        return;
                    }
                    
                    const placeholder = qrContainer.querySelector('.animate-pulse');
                    if (placeholder) {
                        placeholder.remove();
                    }
                    
                    const qrWrapper = document.createElement('div');
                    qrWrapper.className = 'flex justify-center items-center opacity-0 transition-opacity duration-500';
                    qrContainer.appendChild(qrWrapper);
                    
                    new QRCode(qrWrapper, {
                        text: qrUrl,
                        width: 128,
                        height: 128,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });

                    setTimeout(() => {
                        qrWrapper.classList.remove('opacity-0');
                    }, 100);
                    
                    resolve();
                } catch (error) {
                    console.error(`Error al generar QR para el código ${code}:`, error);
                    showAlert(`Error al generar el código QR: ${code}`, 'error');
                    reject(error);
                }
            })));
            
            showAlert('Códigos QR generados exitosamente', 'success');
        }

        // Función para descargar QR individual
        async function downloadQR(code) {
            showLoading(true);
            try {
                showAlert('Preparando el código QR para descargar...', 'info');
                const qrContainer = await waitForQR(code);
                if (!qrContainer) {
                    throw new Error('Contenedor QR no encontrado');
                }

                const canvas = qrContainer.querySelector('canvas');
                if (!canvas) {
                    throw new Error('Canvas QR no encontrado');
                }

                const downloadCanvas = document.createElement('canvas');
                const ctx = downloadCanvas.getContext('2d');
                const padding = 20;
                downloadCanvas.width = canvas.width + (padding * 2);
                downloadCanvas.height = canvas.height + (padding * 2) + 30;
                
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, downloadCanvas.width, downloadCanvas.height);
                ctx.drawImage(canvas, padding, padding);
                
                ctx.fillStyle = 'black';
                ctx.font = '12px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(code, downloadCanvas.width / 2, canvas.height + padding + 20);

                const link = document.createElement('a');
                link.download = `qr-${code}.png`;
                link.href = downloadCanvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showAlert('¡Código QR descargado exitosamente!', 'success');
            } catch (error) {
                console.error('Error al descargar QR:', error);
                showAlert('Error al descargar el código QR: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        }

        // Función para descargar todos los QR
        async function downloadAllQR() {
            showLoading(true);
            try {
                showAlert('Preparando todos los códigos QR para descargar...', 'info');
                const canvas = document.getElementById('printable-canvas');
                if (!canvas) {
                    throw new Error('Canvas no encontrado');
                }

                const ctx = canvas.getContext('2d');
                const qrSize = 200;
                const margin = 20;
                const codesPerRow = 3;
                const codes = window.QR_CONFIG.codes;
                const productName = window.QR_CONFIG.productName;
                
                if (!codes.length) {
                    showAlert('No hay códigos QR para descargar', 'error');
                    return;
                }

                const totalRows = Math.ceil(codes.length / codesPerRow);
                canvas.width = (qrSize + margin * 2) * codesPerRow + margin;
                canvas.height = (qrSize + margin * 2) * totalRows + margin;

                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                await Promise.all(codes.map(code => waitForQR(code)));

                codes.forEach((code, index) => {
                    const row = Math.floor(index / codesPerRow);
                    const col = index % codesPerRow;
                    const x = margin + col * (qrSize + margin * 2);
                    const y = margin + row * (qrSize + margin * 2);
                    
                    const qrCanvas = document.querySelector(`#qr-${code} canvas`);
                    if (qrCanvas) {
                        ctx.strokeStyle = '#ccc';
                        ctx.setLineDash([5, 5]);
                        ctx.strokeRect(x - margin/2, y - margin/2, qrSize + margin, qrSize + margin);
                        ctx.setLineDash([]);

                        ctx.drawImage(qrCanvas, x, y, qrSize, qrSize);

                        ctx.fillStyle = 'black';
                        ctx.font = '12px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText(productName, x + qrSize/2, y + qrSize + 15);
                        ctx.font = '10px Arial';
                        ctx.fillText(code, x + qrSize/2, y + qrSize + 30);
                    }
                });

                const link = document.createElement('a');
                link.download = 'qr-codes-sheet.png';
                link.href = canvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showAlert('¡Hoja de códigos QR descargada exitosamente!', 'success');
            } catch (error) {
                console.error('Error al descargar todos los QR:', error);
                showAlert('Error al descargar los códigos QR: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        }
        // Función para mostrar alertas
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
            } text-white`;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            
            // Remover la alerta después de 3 segundos
            setTimeout(() => {
                alertDiv.classList.add('opacity-0', 'transition-opacity');
                setTimeout(() => alertDiv.remove(), 300);
            }, 3000);
        }

        // Función para mostrar el estado de carga
        function showLoading(show = true) {
            let loadingEl = document.getElementById('loading-overlay');
            if (!loadingEl && show) {
                loadingEl = document.createElement('div');
                loadingEl.id = 'loading-overlay';
                loadingEl.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                loadingEl.innerHTML = `
                    <div class="bg-white p-6 rounded-lg shadow-xl text-center transform scale-100 animate-fadeIn">
                        <div class="relative">
                            <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-200 mx-auto mb-4"></div>
                            <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent absolute top-0 left-1/2 -translate-x-1/2"></div>
                        </div>
                        <p class="text-gray-700 text-lg font-semibold animate-pulse">Generando códigos QR...</p>
                        <p class="text-gray-500 text-sm mt-2">Por favor, espere un momento</p>
                    </div>
                `;
                document.body.appendChild(loadingEl);
            } else if (loadingEl && !show) {
                loadingEl.remove();
            }
        }

        // Función para esperar a que el QR se genere
        function waitForQR(code) {
            return new Promise((resolve, reject) => {
                let attempts = 0;
                const maxAttempts = 10;
                
                const checkQR = () => {
                    const qrContainer = document.querySelector(`#qr-${code}`);
                    const canvas = qrContainer?.querySelector('canvas');
                    
                    if (canvas) {
                        resolve(qrContainer);
                    } else if (attempts >= maxAttempts) {
                        reject(new Error('Tiempo de espera agotado para generar el QR'));
                    } else {
                        attempts++;
                        setTimeout(checkQR, 200);
                    }
                };
                
                checkQR();
            });
        }

        // Función para verificar si la librería QRCode está cargada
        function checkQRCodeLibrary() {
            return new Promise((resolve, reject) => {
                let attempts = 0;
                const maxAttempts = 10;
                
                const check = () => {
                    if (typeof QRCode !== 'undefined') {
                        resolve();
                    } else if (attempts >= maxAttempts) {
                        reject(new Error('No se pudo cargar la librería QRCode'));
                    } else {
                        attempts++;
                        setTimeout(check, 200);
                    }
                };
                
                check();
            });
        }

        // Función para descargar QR individual
        async function downloadQR(code) {
            showLoading(true);
            try {
                showAlert('Preparando el código QR para descargar...', 'info');
                const qrContainer = await waitForQR(code);
                if (!qrContainer) {
                    throw new Error('Contenedor QR no encontrado');
                }

                const canvas = qrContainer.querySelector('canvas');
                if (!canvas) {
                    throw new Error('Canvas QR no encontrado');
                }

                // Crear un nuevo canvas para agregar padding y texto
                const downloadCanvas = document.createElement('canvas');
                const ctx = downloadCanvas.getContext('2d');
                
                // Configurar tamaño con padding
                const padding = 20;
                downloadCanvas.width = canvas.width + (padding * 2);
                downloadCanvas.height = canvas.height + (padding * 2) + 30;
                
                // Fondo blanco
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, downloadCanvas.width, downloadCanvas.height);
                
                // Dibujar el QR
                ctx.drawImage(canvas, padding, padding);
                
                // Agregar el código como texto
                ctx.fillStyle = 'black';
                ctx.font = '12px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(code, downloadCanvas.width / 2, canvas.height + padding + 20);

                // Crear y simular clic en enlace de descarga
                const link = document.createElement('a');
                link.download = `qr-${code}.png`;
                link.href = downloadCanvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showAlert('¡Código QR descargado exitosamente!', 'success');
            } catch (error) {
                console.error('Error al descargar QR:', error);
                showAlert('Error al descargar el código QR: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        }

        // Función para descargar todos los QR
        async function downloadAllQR() {
            showLoading(true);
            try {
                showAlert('Preparando todos los códigos QR para descargar...', 'info');
                const canvas = document.getElementById('printable-canvas');
                if (!canvas) {
                    throw new Error('Canvas no encontrado');
                }

                const ctx = canvas.getContext('2d');
                const qrSize = 200;
                const margin = 20;
                const codesPerRow = 3;
                const codes = Array.from(document.querySelectorAll('[id^="qr-"]')).map(el => el.id.replace('qr-', ''));
                
                if (!codes.length) {
                    showAlert('No hay códigos QR para descargar', 'error');
                    return;
                }

                // Calcular dimensiones del canvas
                const totalRows = Math.ceil(codes.length / codesPerRow);
                canvas.width = (qrSize + margin * 2) * codesPerRow + margin;
                canvas.height = (qrSize + margin * 2) * totalRows + margin;

                // Fondo blanco
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Esperar a que todos los códigos QR estén generados
                await Promise.all(codes.map(code => waitForQR(code)));

                // Dibujar cada código QR
                codes.forEach((code, index) => {
                    const row = Math.floor(index / codesPerRow);
                    const col = index % codesPerRow;
                    const x = margin + col * (qrSize + margin * 2);
                    const y = margin + row * (qrSize + margin * 2);
                    
                    const qrCanvas = document.querySelector(`#qr-${code} canvas`);
                    if (qrCanvas) {
                        // Dibujar líneas de corte
                        ctx.strokeStyle = '#ccc';
                        ctx.setLineDash([5, 5]);
                        ctx.strokeRect(x - margin/2, y - margin/2, qrSize + margin, qrSize + margin);
                        ctx.setLineDash([]);

                        // Dibujar QR
                        ctx.drawImage(qrCanvas, x, y, qrSize, qrSize);

                        // Agregar texto
                        ctx.fillStyle = 'black';
                        ctx.font = '12px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText(code, x + qrSize/2, y + qrSize + 30);
                    }
                });

                // Descargar
                const link = document.createElement('a');
                link.download = 'qr-codes-sheet.png';
                link.href = canvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showAlert('¡Hoja de códigos QR descargada exitosamente!', 'success');
            } catch (error) {
                console.error('Error al descargar todos los QR:', error);
                showAlert('Error al descargar los códigos QR: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        }
    </script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .banner {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Banner Principal -->
    <div class="banner text-white py-16 px-4 mb-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-4xl font-bold mb-4">Sistema de Generación de Códigos QR</h1>
            <p class="text-xl">Genera y administra códigos QR para tus productos de manera eficiente</p>
        </div>
    </div>
    
    <!-- Contenedor Principal -->
    <div class="container mx-auto px-4">
