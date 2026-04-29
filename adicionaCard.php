<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form id="cardForm" method="POST" action="adicionaCard2.php" enctype="multipart/form-data">
        <label for="image">Imagem:</label>
        <input type="file" id="image" name="image" required>

        <label for="title">Título:</label>
        <input type="text" id="title" name="title" required>

        <label for="description">Descrição:</label>
        <textarea id="description" name="description" required></textarea>

        <label for="type">Tipo:</label>
        <select id="type" name="type" required>
            <option value="planets">Planets</option>
            <option value="stars">Stars</option>
            <option value="galaxies">Galáxias</option>
            <option value="cosmology">Cosmologia</option>
            <option value="other">Other</option>
        </select>

        <input type="submit" value="Submit">
    </form>

</body>

</html>