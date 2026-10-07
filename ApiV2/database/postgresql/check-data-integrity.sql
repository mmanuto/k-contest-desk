-- Eseguire dopo l'importazione. Ogni query deve restituire zero.

SELECT count(*) AS inscriptions_without_athlete
FROM athlete_inscriptions ai
LEFT JOIN athletes a ON a.id = ai.athlete_id
WHERE a.id IS NULL;

SELECT count(*) AS inscriptions_without_category
FROM athlete_inscriptions ai
LEFT JOIN categorycodes c ON c.id = ai.categorycode_id
WHERE c.id IS NULL;

SELECT count(*) AS inscriptions_without_competition
FROM athlete_inscriptions ai
LEFT JOIN competitions c ON c.id = ai.competition_id
WHERE c.id IS NULL;

SELECT count(*) AS athletes_without_club
FROM athletes a
LEFT JOIN clubs c ON c.id = a.club_id
WHERE c.id IS NULL;

SELECT count(*) AS tatami_without_user
FROM tatami_assignments t
LEFT JOIN users u ON u.id = t.user_id
WHERE u.id IS NULL;

SELECT count(*) AS tatami_without_category
FROM tatami_assignments t
LEFT JOIN categorycodes c ON c.id = t.categorycode_id
WHERE c.id IS NULL;

SELECT count(*) AS panel_results_without_inscription
FROM results_judged_panel r
LEFT JOIN athlete_inscriptions ai ON ai.id = r.athlete_inscription_id
WHERE ai.id IS NULL;

SELECT count(*) AS timed_results_without_inscription
FROM results_timed r
LEFT JOIN athlete_inscriptions ai ON ai.id = r.athlete_inscription_id
WHERE ai.id IS NULL;

SELECT count(*) AS scores_without_inscription
FROM scores s
LEFT JOIN athlete_inscriptions ai ON ai.id = s.athlete_inscription_id
WHERE s.athlete_inscription_id IS NOT NULL AND ai.id IS NULL;
