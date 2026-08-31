document.addEventListener('DOMContentLoaded', function() {
    const board = document.getElementById('board');

    function loadBoard() {
        mb.ajax({
            url: '/?api=dashboard&action=get_board',
            method: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    renderBoard(response.board);
                } else {
                    board.innerHTML = 'Error: ' + response.error;
                }
            },
            error: function(xhr, status, error) {
                board.innerHTML = 'Error: ' + error;
            }
        });
    }

    function renderBoard(boardData) {
        board.innerHTML = '';
        boardData.columns.forEach(function(column) {
            const columnEl = document.createElement('div');
            columnEl.className = 'board-column';
            columnEl.innerHTML = `
                <div class="column-header">${column.title}</div>
                <div class="cards"></div>
            `;
            const cardsContainer = columnEl.querySelector('.cards');
            column.cards.forEach(function(card) {
                const cardEl = document.createElement('div');
                cardEl.className = 'card';
                cardEl.innerHTML = card.text;
                cardsContainer.appendChild(cardEl);
            });
            board.appendChild(columnEl);
        });
    }

    loadBoard();
});
