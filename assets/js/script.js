document.addEventListener('DOMContentLoaded', function() {
    const optimizeForm = document.getElementById('optimizeForm');
    const optimizeBtn = document.getElementById('optimizeBtn');
    const loadingDiv = document.getElementById('loading');
    const resultsDiv = document.getElementById('results');
    const optimizeAgainBtn = document.getElementById('optimizeAgainBtn');
    const saveForm = document.getElementById('saveOptimizationForm');

    function formatExperience(experiences) {
        let output = '';
        if (Array.isArray(experiences)) {
            experiences.forEach(exp => {
                if (exp.company) {
                    const description = exp.description ? exp.description.replace(/\s*\(Note:.*?\)/g, '') : '';
                    output += '<div class="experience-entry">';
                    output += '<h5>' + (exp.company || '') + '</h5>';
                    output += '<p><strong>' + (exp.position || '') + '</strong></p>';
                    output += '<p><em>' + (exp.start_date || '') + ' - ' + (exp.current ? 'Present' : (exp.end_date || '')) + '</em></p>';
                    if (description) {
                        const lines = description.split('\n').filter(line => line.trim());
                        output += '<ul>';
                        lines.forEach(line => {
                            output += '<li>' + line + '</li>';
                        });
                        output += '</ul>';
                    }
                    output += '</div>';
                }
            });
        }
        return output || 'No experience data available';
    }

    if (optimizeForm) {
        optimizeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (optimizeBtn) optimizeBtn.disabled = true;
            if (loadingDiv) loadingDiv.style.display = 'block';
            if (resultsDiv) resultsDiv.style.display = 'none';

            const formData = new FormData(optimizeForm);
            fetch('../ai/optimize.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }
                console.log(data);
                const optimizedSummary = document.getElementById('optimizedSummary');
                const optimizedExperience = document.getElementById('optimizedExperience');
                const originalScoreEl = document.getElementById('originalScore');
                const optimizedScoreEl = document.getElementById('optimizedScore');
                const scoreProgressOriginal = document.getElementById('scoreProgressOriginal');
                const scoreProgressOptimized = document.getElementById('scoreProgressOptimized');
                const scoreDiff = document.getElementById('scoreDiff');

                if (optimizedSummary) optimizedSummary.innerHTML = data.optimized_summary?.optimized || 'No optimization suggested';
                if (optimizedExperience) {
                    const cleanedExperience = (data.optimized_experience || []).map(exp => {
                        const optimized = exp.optimized || exp;
                        if (optimized.description) {
                            optimized.description = optimized.description.replace(/\s*\(Note:.*?\)/g, '');
                        }
                        return optimized;
                    });
                    optimizedExperience.innerHTML = formatExperience(cleanedExperience);
                }
                if (data.overall_score) {
                    const originalScore = Math.round(data.overall_score.original * 10) / 10;
                    const optimizedScore = Math.round(data.overall_score.optimized * 10) / 10;
                    const scoreIncrease = optimizedScore - originalScore;

                    if (originalScoreEl) originalScoreEl.textContent = originalScore;
                    if (optimizedScoreEl) optimizedScoreEl.textContent = optimizedScore;
                    if (scoreProgressOriginal) scoreProgressOriginal.style.width = `${originalScore * 10}%`;
                    if (scoreProgressOptimized) scoreProgressOptimized.style.width = `${optimizedScore * 10}%`;
                    if (scoreDiff) {
                        scoreDiff.textContent = `${scoreIncrease >= 0 ? '+' : ''}${scoreIncrease.toFixed(1)}`;
                        scoreDiff.className = scoreIncrease >= 0 ? 'score-diff positive' : 'score-diff negative';
                    }

                    document.getElementById('original_score_input').value = originalScore;
                    document.getElementById('optimized_score_input').value = optimizedScore;
                }

                if (saveForm) {
                    const cleanedExperience = (data.optimized_experience || []).map(exp => {
                        const optimized = exp.optimized || exp;
                        if (optimized.description) {
                            optimized.description = optimized.description.replace(/\s*\(Note:.*?\)/g, '');
                        }
                        return optimized;
                    });
                    document.getElementById('optimized_summary_input').value = data.optimized_summary?.optimized || '';
                    document.getElementById('optimized_experience_input').value = JSON.stringify(cleanedExperience);
                    saveForm.querySelector('[name="resume_id"]').value = formData.get('resume_id');
                    saveForm.querySelector('[name="job_description"]').value = formData.get('job_description');
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

    if (optimizeAgainBtn && optimizeForm && loadingDiv && resultsDiv) {
        optimizeAgainBtn.addEventListener('click', function() {
            loadingDiv.style.display = 'block';
            resultsDiv.style.display = 'none';
            optimizeForm.dispatchEvent(new Event('submit'));
        });
    }

    if (saveForm) {
        saveForm.addEventListener('submit', function(e) {
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