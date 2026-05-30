document.addEventListener('DOMContentLoaded', () => {

    const typeSelect = document.getElementById('ticketType');
    const seatSelect = document.getElementById('ticketSeat');
    const seatGroup = document.getElementById('ticketSeatGroup');
    const addBtn = document.getElementById('btnAddTicket');

    if (!typeSelect || !seatSelect || !addBtn || !window.ticketsByType) {
        return;
    }

    let selectedTicket = null;
    const toggleSeatSelector = (isVisible) => {
        if (seatGroup) {
            seatGroup.hidden = !isVisible;
        }
    };

    typeSelect.addEventListener('change', () => {

        const type = typeSelect.value;

        seatSelect.innerHTML = '';
        seatSelect.disabled = true;
        addBtn.disabled = true;
        selectedTicket = null;
        toggleSeatSelector(true);

        if (!type || !window.ticketsByType[type]) {
            seatSelect.innerHTML = '<option>Selecciona un tipo primero</option>';
            return;
        }

        // 🔑 IMPORTANTE: convertir a array real
        const tickets = Object.values(window.ticketsByType[type]);

        // 🎫 CASO GENERAL (1 solo ticket con stock > 1)
        if (tickets.length === 1 && tickets[0].stock > 1) {

            seatSelect.disabled = true;
            seatSelect.innerHTML = `<option>No aplica</option>`;
            toggleSeatSelector(false);

            selectedTicket = tickets[0];
            addBtn.disabled = false;
            return;
        }

        // 🎟️ CASO NUMERADO
        seatSelect.disabled = false;
        seatSelect.innerHTML = '<option value="">Selecciona asiento</option>';

        tickets.forEach(ticket => {

            // Seguridad extra
            if (ticket.stock <= 0) return;

            const option = document.createElement('option');
            option.value = ticket.id;
            option.textContent = ticket.name;
            option.dataset.price = ticket.total_price;

            seatSelect.appendChild(option);
        });
    });

    seatSelect.addEventListener('change', () => {

        const ticketId = seatSelect.value;

        if (!ticketId) {
            selectedTicket = null;
            addBtn.disabled = true;
            return;
        }

        selectedTicket = Object.values(window.ticketsByType)
            .flatMap(group => Object.values(group))
            .find(t => t.id == ticketId);

        addBtn.disabled = !selectedTicket;
    });

    addBtn.addEventListener('click', () => {

        if (!selectedTicket) return;

        addToCart({
            id: selectedTicket.id,
            name: selectedTicket.name,
            total_price: selectedTicket.total_price,
            stock: selectedTicket.stock,
            qty: 1,
            svg_selector: null
        });
    });

});
