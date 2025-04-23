function submitPost() {
    const postContent = document.getElementById('postContent').value;
    if (postContent.trim() !== "") {
        fetch('/post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ content: postContent })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Ваш пост опубликован.");
                document.getElementById('postContent').value = ""; // Очистить поле после публикации
                loadPosts(); // Обновить список постов
            } else {
                alert("Ошибка при публикации поста.");
            }
        });
    } else {
        alert("Пожалуйста, введите текст поста.");
    }
}

function loadPosts() {
    fetch('/get_posts.php')
        .then(response => response.json())
        .then(posts => {
            const postList = document.getElementById('postList');
            postList.innerHTML = ''; // Очистить список перед добавлением новых постов
            posts.forEach(post => {
                const postElement = document.createElement('div');
                postElement.textContent = post.content;
                postList.appendChild(postElement);
            });
        });
}

// Загрузка постов при загрузке страницы
window.onload = loadPosts;