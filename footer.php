    </div>
    
    <div class="footer">
        <div class="footer-container">
            <p>© <?php echo date('Y'); ?> Каталог дверей. Все права защищены.</p>
            <p>Телефон: +7 (XXX) XXX-XX-XX | Email: info@doors.ru</p>
            <p>Режим работы: Пн-Пт 9:00-18:00, Сб 10:00-16:00</p>
        </div>
    </div>
    
    <script>
    function updateCartCount(count) {
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
            cartCountElement.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    }
    </script>
</body>
</html>