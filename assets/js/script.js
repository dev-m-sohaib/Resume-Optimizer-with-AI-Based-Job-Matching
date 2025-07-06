document.addEventListener('DOMContentLoaded', function() {
    const optimizeForm = document.getElementById('optimizeForm');
    const optimizeBtn = document.getElementById('optimizeBtn');
    const loadingDiv = document.getElementById('loading');
    const resultsDiv = document.getElementById('results');
    const optimizeAgainBtn = document.getElementById('optimizeAgainBtn');
    const saveForm = document.getElementById('saveOptimizationForm');

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

                const optimizedSummary = document.getElementById('optimizedSummary');
                const optimizedExperience = document.getElementById('optimizedExperience');
                const originalScoreEl = document.getElementById('originalScore');
                const optimizedScoreEl = document.getElementById('optimizedScore');
                const scoreProgressOriginal = document.getElementById('scoreProgressOriginal');
                const scoreProgressOptimized = document.getElementById('scoreProgressOptimized');
                const scoreDiff = document.getElementById('scoreDiff');
                const originalScoreText = document.getElementById("originalScore").textContent.trim();
                const optimizedScoreText = document.getElementById("optimizedScore").textContent.trim();
    

                if (optimizedSummary) optimizedSummary.textContent = data.optimized_summary?.optimized || 'No optimization suggested';
                if (optimizedExperience) optimizedExperience.textContent = data.optimized_experience?.optimized || 'No optimization suggested';
                document.getElementById("original_score_input").value = originalScoreText;
                document.getElementById("optimized_score_input").value = optimizedScoreText;
                if (data.overall_score) {
                    const originalScore = Math.round(data.overall_score.original * 10) / 10;
                    const optimizedScore = Math.round(data.overall_score.optimized * 10) / 10;
                    const scoreIncrease = optimizedScore - originalScore;

                    if (originalScoreEl) originalScoreEl.textContent = originalScore;
                    if (optimizedScoreEl) optimizedScoreEl.textContent = optimizedScore;
                    if (scoreProgressOriginal) scoreProgressOriginal.style.width = `${originalScore * 10}%`;
                    if (scoreProgressOptimized) scoreProgressOptimized.style.width = `${optimizedScore * 10}%`;
                    if (scoreDiff) {
                        scoreDiff.textContent = `+${scoreIncrease.toFixed(1)}`;
                        scoreDiff.className = scoreIncrease >= 0 ? 'score-diff positive' : 'score-diff negative';
                    }
                }

                if (saveForm) {
                    const setField = (name, value) => {
                        const input = saveForm.querySelector(`[name="${name}"]`);
                        if (input) input.value = value || '';
                    };
                    setField('resume_id', formData.get('resume_id'));
                    setField('job_description', formData.get('job_description'));
                    setField('optimized_summary', data.optimized_summary?.optimized);
                    setField('optimized_experience', data.optimized_experience?.optimized);
                    setField('original_score', data.overall_score?.original || 0);
                    setField('optimized_score', data.overall_score?.optimized || 0);
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