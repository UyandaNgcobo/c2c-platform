<script>
        const toggleBtn = document.getElementById('toggle-btn');
        const sidebar = document.getElementById('sidebar');

        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            toggleBtn.classList.toggle('rotated'); 
        });

        function toggleSidebar() {
    const sidebar = document.getElementById('mobile-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

document.getElementById('toggle-btn').addEventListener('click', toggleSidebar);
    </script>
</body>
</html>