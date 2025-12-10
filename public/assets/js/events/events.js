document.addEventListener('DOMContentLoaded', function () {

    const sourceType = document.getElementById("source_type");
    const projectSelect = document.getElementById("project_id");
    const phaseSelect = document.getElementById("phase_id");
    const stageSelect = document.getElementById("stage_id");

    let nabooProjects = [];
    let adaraProjects = projectSelect.dataset.adaraProjects ? JSON.parse(projectSelect.dataset.adaraProjects) : [];

    function resetSelect(select, message) {
        select.innerHTML = `<option value="">${message}</option>`;
        select.disabled = true;
    }

    async function fetchJson(url) {
        const res = await fetch(url);
        if (!res.ok) throw new Error("Error en la petición");
        return res.json();
    }

    async function loadPhasesFromProject(projectId) {
        resetSelect(phaseSelect, "Cargando fases...");
        resetSelect(stageSelect, "Seleccione una fase primero");

        if (sourceType.value === 'naboo') {
            const project = nabooProjects.find(p => p.id == projectId);
            if (!project || !project.phases) {
                resetSelect(phaseSelect, "No hay fases disponibles");
                return;
            }
            phaseSelect.innerHTML = `<option value="">Seleccione una fase...</option>`;
            project.phases.forEach(phase => {
                phaseSelect.innerHTML += `<option value="${phase.id}">${phase.name}</option>`;
            });
            phaseSelect.disabled = false;
        } else {
            try {
                const data = await fetchJson(`/api/projects/${projectId}/phases`);
                phaseSelect.innerHTML = `<option value="">Seleccione una fase...</option>`;
                data.forEach(phase => {
                    phaseSelect.innerHTML += `<option value="${phase.id}">${phase.name}</option>`;
                });
                phaseSelect.disabled = false;
            } catch {
                resetSelect(phaseSelect, "Error al cargar fases");
            }
        }
    }

    async function loadStagesFromPhase(projectId, phaseId) {
        resetSelect(stageSelect, "Cargando etapas...");

        if (sourceType.value === 'naboo') {
            const project = nabooProjects.find(p => p.id == projectId);
            const phase = project?.phases?.find(ph => ph.id == phaseId);
            if (!phase || !phase.stages) {
                resetSelect(stageSelect, "No hay etapas disponibles");
                return;
            }
            stageSelect.innerHTML = `<option value="">Seleccione una etapa...</option>`;
            phase.stages.forEach(stage => {
                stageSelect.innerHTML += `<option value="${stage.id}">${stage.name}</option>`;
            });
            stageSelect.disabled = false;
        } else {
            try {
                const data = await fetchJson(`/api/projects/${projectId}/phases/${phaseId}/stages`);
                stageSelect.innerHTML = `<option value="">Seleccione una etapa...</option>`;
                data.forEach(stage => {
                    stageSelect.innerHTML += `<option value="${stage.id}">${stage.name}</option>`;
                });
                stageSelect.disabled = false;
            } catch {
                resetSelect(stageSelect, "Error al cargar etapas");
            }
        }
    }

    projectSelect.addEventListener("change", async function () {
        const projectId = this.value;
        resetSelect(phaseSelect, "Seleccione un proyecto primero");
        resetSelect(stageSelect, "Seleccione una fase primero");
        if (!projectId) return;
        await loadPhasesFromProject(projectId);
    });

    phaseSelect.addEventListener("change", async function () {
        const projectId = projectSelect.value;
        const phaseId = this.value;
        resetSelect(stageSelect, "Seleccione una fase primero");
        if (!projectId || !phaseId) return;
        await loadStagesFromPhase(projectId, phaseId);
    });

    sourceType.addEventListener("change", async function () {
        const type = this.value;

        resetSelect(projectSelect, "Seleccione un proyecto...");
        resetSelect(phaseSelect, "Seleccione un proyecto primero");
        resetSelect(stageSelect, "Seleccione una fase primero");

        if (type === 'adara') {
            projectSelect.innerHTML = `<option value="">Seleccione un proyecto...</option>`;
            adaraProjects.forEach(p => {
                projectSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
            });
            projectSelect.disabled = false;
        } else if (type === 'naboo') {
            try {
                const data = await fetchJson(`/api/projects`);
                nabooProjects = data;
                projectSelect.innerHTML = `<option value="">Seleccione un proyecto...</option>`;
                data.forEach(p => {
                    projectSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
                });
                projectSelect.disabled = false;
            } catch {
                resetSelect(projectSelect, "Error al cargar proyectos");
            }
        }

        // Selección automática en edición
        if (window.selectedProject) {
            projectSelect.value = window.selectedProject;
            await loadPhasesFromProject(window.selectedProject);

            if (window.selectedPhase) {
                phaseSelect.value = window.selectedPhase;
                await loadStagesFromPhase(window.selectedProject, window.selectedPhase);

                if (window.selectedStage) {
                    stageSelect.value = window.selectedStage;
                }
            }
        }
    });

    // Disparar cambio inicial para cargar proyectos
    sourceType.dispatchEvent(new Event('change'));
});
