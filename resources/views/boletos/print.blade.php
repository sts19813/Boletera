<!DOCTYPE html>
<html>
<head>
    <title>Imprimiendo boletos</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        iframe {
            width: 100%;
            height: 100vh;
            border: none;
        }
    </style>
</head>
<body>

<iframe src="{{ $pdf }}" onload="autoPrint()"></iframe>

<script>
function autoPrint() {
    setTimeout(() => {
        window.focus();
        window.print();
    }, 500);
}
</script>

</body>
</html>
