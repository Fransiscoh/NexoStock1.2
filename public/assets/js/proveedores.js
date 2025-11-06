document.addEventListener('DOMContentLoaded', () => {
    const addProviderForm = document.getElementById('addProviderForm');
    const cancelEditProvider = document.getElementById('cancelEditProvider');
    
    if (addProviderForm) {
        addProviderForm.addEventListener('submit', handleProviderFormSubmit);
    }
    
    if (cancelEditProvider) {
        cancelEditProvider.addEventListener('click', cancelEditMode);
    }
});

function handleProviderFormSubmit(event) {
    event.preventDefault();

    const providerId = document.getElementById('providerId').value;
    const providerName = document.getElementById('providerName').value;
    const providerContact = document.getElementById('providerContact').value;
    const providerPhone = document.getElementById('providerPhone').value;
    const providerEmail = document.getElementById('providerEmail').value;
    const providerAddress = document.getElementById('providerAddress').value;

    const url = providerId ? '../backend/index.php?accion=editar_proveedor' : '../backend/index.php?accion=agregar_proveedor';

    const providerData = {
        id: providerId,
        nombre: providerName,
        contacto: providerContact,
        telefono: providerPhone,
        email: providerEmail,
        direccion: providerAddress
    };

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(providerData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showNotification(data.error, 'error');
        } else {
            window.fetchData(); // Recargar todos los datos
            resetProviderForm();
            showNotification(providerId ? 'Proveedor actualizado' : 'Proveedor agregado', 'success');
        }
    });
}

function editProvider(id) {
    const provider = providers.find(p => p.id == id);
    if (provider) {
        document.getElementById('providerId').value = provider.id;
        document.getElementById('providerName').value = provider.nombre;
        document.getElementById('providerContact').value = provider.contacto || '';
        document.getElementById('providerPhone').value = provider.telefono || '';
        document.getElementById('providerEmail').value = provider.email || '';
        document.getElementById('providerAddress').value = provider.direccion || '';

        document.querySelector('#addProviderForm button[type="submit"]').innerHTML = '<i class="fas fa-edit"></i> Actualizar Proveedor';
        document.getElementById('cancelEditProvider').style.display = 'inline-block';
    }
}

function deleteProvider(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este proveedor?')) {
        fetch('../backend/index.php?accion=eliminar_proveedor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showNotification(data.error, 'error');
            } else {
                window.fetchData(); // Recargar todos los datos
                showNotification('Proveedor eliminado', 'success');
            }
        });
    }
}

function cancelEditMode() {
    resetProviderForm();
}

function resetProviderForm() {
    document.getElementById('addProviderForm').reset();
    document.getElementById('providerId').value = '';
    document.querySelector('#addProviderForm button[type="submit"]').innerHTML = '<i class="fas fa-plus"></i> Agregar Proveedor';
    document.getElementById('cancelEditProvider').style.display = 'none';
}

// Make functions available globally for inline onclick handlers
window.handleProviderFormSubmit = handleProviderFormSubmit;
window.editProvider = editProvider;
window.deleteProvider = deleteProvider;
window.cancelEditMode = cancelEditMode;
