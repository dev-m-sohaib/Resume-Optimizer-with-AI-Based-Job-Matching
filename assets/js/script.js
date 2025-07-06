document.addEventListener('DOMContentLoaded', function () {
    const optimizeForm = document.getElementById('optimizeForm');
    const optimizeBtn = document.getElementById('optimizeBtn');
    const loadingDiv = document.getElementById('loading');
    const resultsDiv = document.getElementById('results');
    const optimizeAgainBtn = document.getElementById('optimizeAgainBtn');
    const saveForm = document.getElementById('saveOptimizationForm');

    if (optimizeForm) {
        optimizeForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (optimizeBtn) optimizeBtn.disabled = true;
            if (loadingDiv) loadingDiv.style.display = 'block';
            if (resultsDiv) resultsDiv.style.display = 'none';

            const formData = new FormData(optimizeForm);
            fetch('../ai/optimize.php', {method: 'POST',body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    const optimizedSummary = document.getElementById('optimizedSummary');
                    const optimizedExperience = document.getElementById('optimizedExperience'); 

                    if (optimizedSummary) optimizedSummary.textContent = data.optimized_summary?.optimized || 'No optimization suggested';
                    if (optimizedExperience) optimizedExperience.textContent = data.optimized_experience?.optimized || 'No optimization suggested'; 
                    if (saveForm) {
                        const setField = (name, value) => {
                            const input = saveForm.querySelector(`[name="${name}"]`);
                            if (input) input.value = value || '';
                        };
                        setField('resume_id', formData.get('resume_id'));
                        setField('job_description', formData.get('job_description'));
                        setField('optimized_summary', data.optimized_summary?.optimized);
                        setField('optimized_experience', data.optimized_experience?.optimized); 
                    }
                    if (resultsDiv) resultsDiv.style.display = 'block';
                })
                .catch(error => { 
                    alert('Something went wrong. Please try again.');
                })
                .finally(() => {
                    if (optimizeBtn) optimizeBtn.disabled = false;
                    if (loadingDiv) loadingDiv.style.display = 'none';
                });
        });
    }

    // Optimize again
    if (optimizeAgainBtn && optimizeForm && loadingDiv && resultsDiv) {
        optimizeAgainBtn.addEventListener('click', function () {
            loadingDiv.style.display = 'block';
            resultsDiv.style.display = 'none';
            optimizeForm.dispatchEvent(new Event('submit'));
        });
    }

    if (saveForm) {
        saveForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const saveBtn = saveForm.querySelector('button[type="submit"]');

            if (!saveBtn) return;

            saveBtn.disabled = true;
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Saving...';

            fetch('../ai/save_optimization.php', {
                method: 'POST',
                body: new FormData(saveForm)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Optimization saved successfully!');
                        window.location.href = `optimize.php?view=${data.optimization_id}`;
                    } else {
                        alert('Error: ' + (data.error || 'Save failed.'));
                    }
                })
                .catch(error => { 
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = originalText;
                });
        });
    } 
});
