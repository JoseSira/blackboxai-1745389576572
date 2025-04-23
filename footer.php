</div> <!-- Cierre del contenedor principal -->
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12 py-8">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Sistema de Generación QR. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts globales -->
    <script>
        // Función para mostrar mensajes de alerta
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white`;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 3000);
        }

        // Inicializar todos los tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Aquí puedes agregar inicializaciones adicionales si son necesarias
        });
    </script>
</body>
</html>
