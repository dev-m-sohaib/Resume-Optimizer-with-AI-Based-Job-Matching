
function addEducationEntry() {
    const container = document.getElementById('educationEntries');
    const newEntry = document.createElement('div');
    newEntry.className = 'entry-container';
    newEntry.id = 'eduEntry_' + educationEntryCount;
    
    newEntry.innerHTML = `
        <div class="entry-header">
            <h5>Education #${educationEntryCount + 1}</h5>
            <span class="remove-entry" onclick="removeEntry('eduEntry_${educationEntryCount}')">&times;</span>
        </div>
        <div class="mb-3">
            <label class="form-label">Institution</label>
            <input type="text" name="edu_institution_${educationEntryCount}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Degree</label>
            <input type="text" name="edu_degree_${educationEntryCount}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Field of Study</label>
            <input type="text" name="edu_field_of_study_${educationEntryCount}" class="form-control">
        </div>
        <div class="date-fields mb-3">
            <div class="date-field">
                <label class="form-label">Start Date</label>
                <input type="month" name="edu_start_date_${educationEntryCount}" class="form-control">
            </div>
            <div class="date-field" id="eduEndDateContainer_${educationEntryCount}">
                <label class="form-label">End Date</label>
                <input type="month" name="edu_end_date_${educationEntryCount}" class="form-control">
            </div>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="edu_current_${educationEntryCount}" class="form-check-input current-checkbox" 
                   id="eduCurrent_${educationEntryCount}" 
                   onchange="toggleEndDate('eduEndDateContainer_${educationEntryCount}', 'edu_end_date_${educationEntryCount}', this)">
            <label class="form-check-label" for="eduCurrent_${educationEntryCount}">Currently attending</label>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="edu_description_${educationEntryCount}" class="form-control" rows="3"></textarea>
        </div>
    `;
    
    container.appendChild(newEntry);
    educationEntryCount++;
}

function addExperienceEntry() {
    const container = document.getElementById('experienceEntries');
    const newEntry = document.createElement('div');
    newEntry.className = 'entry-container';
    newEntry.id = 'expEntry_' + experienceEntryCount;
    
    newEntry.innerHTML = `
        <div class="entry-header">
            <h5>Experience #${experienceEntryCount + 1}</h5>
            <span class="remove-entry" onclick="removeEntry('expEntry_${experienceEntryCount}')">&times;</span>
        </div>
        <div class="mb-3">
            <label class="form-label">Company</label>
            <input type="text" name="exp_company_${experienceEntryCount}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Position</label>
            <input type="text" name="exp_position_${experienceEntryCount}" class="form-control" required>
        </div>
        <div class="date-fields mb-3">
            <div class="date-field">
                <label class="form-label">Start Date</label>
                <input type="month" name="exp_start_date_${experienceEntryCount}" class="form-control">
            </div>
            <div class="date-field" id="expEndDateContainer_${experienceEntryCount}">
                <label class="form-label">End Date</label>
                <input type="month" name="exp_end_date_${experienceEntryCount}" class="form-control">
            </div>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="exp_current_${experienceEntryCount}" class="form-check-input current-checkbox" 
                   id="expCurrent_${experienceEntryCount}" 
                   onchange="toggleEndDate('expEndDateContainer_${experienceEntryCount}', 'exp_end_date_${experienceEntryCount}', this)">
            <label class="form-check-label" for="expCurrent_${experienceEntryCount}">I currently work here</label>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="exp_description_${experienceEntryCount}" class="form-control" rows="3"></textarea>
        </div>
    `;
    
    container.appendChild(newEntry);
    experienceEntryCount++;
}

function removeEntry(entryId) {
    const entry = document.getElementById(entryId);
    if (entry && confirm('Are you sure you want to remove this entry?')) {
        entry.remove();
        renumberEntries();
    }
}

function renumberEntries() {
    const eduContainers = document.querySelectorAll('#educationEntries .entry-container');
    eduContainers.forEach((container, index) => {
        const oldId = container.id.split('_')[1];
        container.id = `eduEntry_${index}`;
        
        const inputs = container.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.name = input.name.replace(`_${oldId}`, `_${index}`);
            if (input.id) {
                input.id = input.id.replace(`_${oldId}`, `_${index}`);
            }
        });
        const labels = container.querySelectorAll('label');
        labels.forEach(label => {
            if (label.htmlFor) {
                label.htmlFor = label.htmlFor.replace(`_${oldId}`, `_${index}`);
            }
        });
        const header = container.querySelector('.entry-header h5');
        if (header) {
            header.textContent = `Education #${index + 1}`;
        }
    });
    const expContainers = document.querySelectorAll('#experienceEntries .entry-container');
    expContainers.forEach((container, index) => {
        const oldId = container.id.split('_')[1];
        container.id = `expEntry_${index}`;
        
        const inputs = container.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.name = input.name.replace(`_${oldId}`, `_${index}`);
            if (input.id) {
                input.id = input.id.replace(`_${oldId}`, `_${index}`);
            }
        });
        
        const labels = container.querySelectorAll('label');
        labels.forEach(label => {
            if (label.htmlFor) {
                label.htmlFor = label.htmlFor.replace(`_${oldId}`, `_${index}`);
            }
        });
        
        const header = container.querySelector('.entry-header h5');
        if (header) {
            header.textContent = `Experience #${index + 1}`;
        }
    });
    
    educationEntryCount = eduContainers.length;
    experienceEntryCount = expContainers.length;
}

function toggleEndDate(containerId, endDateId, checkbox) {
    const endDateContainer = document.getElementById(containerId);
    const endDateInput = document.querySelector(`[name="${endDateId}"]`);
    
    if (checkbox.checked) {
        endDateInput.disabled = true;
        endDateInput.value = '';
    } else {
        endDateInput.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.current-checkbox').forEach(checkbox => {
        const name = checkbox.name;
        const prefix = name.includes('edu') ? 'edu' : 'exp';
        const id = name.split('_').pop();
        const containerId = `${prefix}EndDateContainer_${id}`;
        const endDateId = `${prefix}_end_date_${id}`;
        
        toggleEndDate(containerId, endDateId, checkbox);
    });
});