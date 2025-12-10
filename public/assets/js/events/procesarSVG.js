document.getElementById("svg_input").addEventListener("change", function (event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = function (e) {
        const text = e.target.result;

        // Parsear SVG
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(text, "image/svg+xml");

        // Etiquetas finales que nos interesan
        const targetTags = ["path", "polygon", "rect", "circle", "polyline", "line", "g"];

        const allElements = xmlDoc.querySelectorAll("*");

        let maxDepth = 0;

        // Detectar profundidad máxima
        allElements.forEach(el => {
            if (targetTags.includes(el.tagName)) {
                let depth = 0;
                let parent = el;

                // Subir por los padres hasta llegar al <svg>
                while (parent && parent.tagName !== "svg") {
                    depth++;
                    parent = parent.parentElement;
                }

                if (depth > maxDepth) {
                    maxDepth = depth;
                }
            }
        });

        // Construir selector basado en profundidad
        let selector = "svg";
        for (let i = 0; i < maxDepth; i++) {
            selector += " *";
        }

        // Escribir resultado
        document.getElementById("modal_selector").value = selector;

        console.log("Selector generado:", selector);
    };

    reader.readAsText(file);
});
