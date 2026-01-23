document.addEventListener('DOMContentLoaded', function () {

    const projectSelect = document.getElementById("project_id");
    const phaseSelect   = document.getElementById("phase_id");
    const stageSelect   = document.getElementById("stage_id");

    let projects = [];

    /* ===============================
       HELPERS
    ================================ */

    function resetSelect(select, placeholder) {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = true;
    }

    async function fetchJson(url) {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Error al consumir API');
        }
        return response.json();
    }

    /* ===============================
       CARGA DE PROYECTOS
    ================================ */

    async function loadProjects() {
        resetSelect(projectSelect, 'Cargando proyectos...');
        resetSelect(phaseSelect, 'Seleccione un proyecto primero');
        resetSelect(stageSelect, 'Seleccione una fase primero');

        try {
            projects = await fetchJson('/api/projects');

            projectSelect.innerHTML = `<option value="">Seleccione un proyecto...</option>`;
            projects.forEach(project => {
                projectSelect.innerHTML += `
                    <option value="${project.id}">
                        ${project.name}
                    </option>`;
            });

            projectSelect.disabled = false;

            // Precarga en edición
            if (window.selectedProject) {
                projectSelect.value = window.selectedProject;
                await loadPhases(window.selectedProject);
            }

        } catch (error) {
            resetSelect(projectSelect, 'Error al cargar proyectos');
            console.error(error);
        }
    }

    /* ===============================
       CARGA DE FASES
    ================================ */

    async function loadPhases(projectId) {
        resetSelect(phaseSelect, 'Cargando fases...');
        resetSelect(stageSelect, 'Seleccione una fase primero');

        const project = projects.find(p => p.id == projectId);

        if (!project || !project.phases || project.phases.length === 0) {
            resetSelect(phaseSelect, 'No hay fases disponibles');
            return;
        }

        phaseSelect.innerHTML = `<option value="">Seleccione una fase...</option>`;
        project.phases.forEach(phase => {
            phaseSelect.innerHTML += `
                <option value="${phase.id}">
                    ${phase.name}
                </option>`;
        });

        phaseSelect.disabled = false;

        // Precarga en edición
        if (window.selectedPhase) {
            phaseSelect.value = window.selectedPhase;
            await loadStages(projectId, window.selectedPhase);
        }
    }

    /* ===============================
       CARGA DE ETAPAS
    ================================ */

    async function loadStages(projectId, phaseId) {
        resetSelect(stageSelect, 'Cargando etapas...');

        const project = projects.find(p => p.id == projectId);
        const phase   = project?.phases?.find(ph => ph.id == phaseId);

        if (!phase || !phase.stages || phase.stages.length === 0) {
            resetSelect(stageSelect, 'No hay etapas disponibles');
            return;
        }

        stageSelect.innerHTML = `<option value="">Seleccione una etapa...</option>`;
        phase.stages.forEach(stage => {
            stageSelect.innerHTML += `
                <option value="${stage.id}">
                    ${stage.name}
                </option>`;
        });

        stageSelect.disabled = false;

        // Precarga en edición
        if (window.selectedStage) {
            stageSelect.value = window.selectedStage;
        }
    }

    /* ===============================
       EVENTOS
    ================================ */

    projectSelect.addEventListener('change', async function () {
        const projectId = this.value;

        resetSelect(phaseSelect, 'Seleccione un proyecto primero');
        resetSelect(stageSelect, 'Seleccione una fase primero');

        if (!projectId) return;

        await loadPhases(projectId);
    });

    phaseSelect.addEventListener('change', async function () {
        const phaseId   = this.value;
        const projectId = projectSelect.value;

        resetSelect(stageSelect, 'Seleccione una fase primero');

        if (!projectId || !phaseId) return;

        await loadStages(projectId, phaseId);
    });

    /* ===============================
       INIT
    ================================ */

    loadProjects();

});
