    const formData = new FormData(form);
    const userId = formData.get('id');

    if (!formData.get('nombre') || !formData.get('apellido') || !formData.get('email')) {
        showNotification('Por favor, complete todos los campos requeridos.', 'warning');
        return;
    }
    if (!userId && !formData.get('password')) {
        showNotification('La contraseña es obligatoria para nuevos usuarios.', 'warning');
        return;
    }

    const url = userId ? '/api/actualizar_usuario' : '/api/crear_usuario';

    fetch(url, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(userId ? 'Usuario actualizado con éxito.' : 'Usuario creado con éxito.', 'success');
            closeUserModal();
    