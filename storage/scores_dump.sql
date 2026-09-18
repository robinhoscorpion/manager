-- =========================================
-- MATRIZ DE PONTUAÇÕES, RESORTS E ACOMODAÇÕES
-- =========================================

-- 1. RESORTS
INSERT INTO point_resorts (id, 
ame, icon, color_theme, created_at, updated_at) VALUES (1, 'RESENDE IMPERIAL', '🌿', 'bg-[#3d5a2e]', NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), icon = VALUES(icon), color_theme = VALUES(color_theme);
INSERT INTO point_resorts (id, 
ame, icon, color_theme, created_at, updated_at) VALUES (2, 'TERRA BOA', '🌿', 'bg-[#3d5a2e]', NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), icon = VALUES(icon), color_theme = VALUES(color_theme);
INSERT INTO point_resorts (id, 
ame, icon, color_theme, created_at, updated_at) VALUES (3, 'VIRA CANOA', '🌴', 'bg-[#5a3d1e]', NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), icon = VALUES(icon), color_theme = VALUES(color_theme);
INSERT INTO point_resorts (id, 
ame, icon, color_theme, created_at, updated_at) VALUES (4, 'PEDRA TORTA', '🪨', 'bg-[#2e3d5a]', NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), icon = VALUES(icon), color_theme = VALUES(color_theme);

-- 2. ACOMODAÇÕES
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (1, 1, 'CLASSIC TÉRREO OU SUPERIOR SEM VISTA', 'CLASSIC TÉRREO OU SUPERIOR SEM VISTA', 4, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (2, 1, 'CLASSIC SUPERIOR COM VISTA PISCINA', 'CLASSIC SUPERIOR COM VISTA PISCINA', 4, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (3, 1, 'SUÍTE IMPERIAL', 'SUÍTE IMPERIAL', 2, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (4, 1, 'SUÍTE REAL', 'SUÍTE REAL', 2, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (5, 2, 'LUXO TÉRREO', 'LUXO TÉRREO', 3, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (6, 2, 'LUXO SUPERIOR', 'LUXO SUPERIOR', 4, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (7, 2, 'SUÍTE MASTER', 'SUÍTE MASTER', 2, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (8, 2, 'SUÍTE MASTER LUXO', 'SUÍTE MASTER LUXO', 2, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (9, 3, 'QUARTO OU BANGALÔ TÉRREO', 'QUARTO OU BANGALÔ TÉRREO', 3, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (10, 3, 'QUARTO OU BANGALÔ LUXO SUPERIOR', 'QUARTO OU BANGALÔ LUXO SUPERIOR', 3, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (11, 4, 'LUXO TÉRREO', 'LUXO TÉRREO', 3, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (12, 4, 'LUXO SUPERIOR', 'LUXO SUPERIOR', 3, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);
INSERT INTO point_accommodations (id, esort_id, 
ame, group_name, max_pax, created_at, updated_at) VALUES (13, 4, 'SUÍTE MASTER', 'SUÍTE MASTER', 22, NOW(), NOW()) ON DUPLICATE KEY UPDATE 
ame = VALUES(
ame), group_name = VALUES(group_name), max_pax = VALUES(max_pax);

-- 3. PONTUAÇÕES (MATRIZ DE PONTOS)
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 1, 2, 35000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 1, 3, 45500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 1, 4, 56000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 2, 2, 45000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 2, 3, 58500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 2, 4, 72000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 3, 2, 60000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 3, 3, 78000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 3, 4, 96000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 4, 2, 90000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 4, 3, 117000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 4, 4, 144000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 5, 2, 145000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 5, 3, 188500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (1, 5, 4, 232000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 1, 3, 54600, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 1, 4, 67200, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 2, 3, 70200, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 2, 4, 86400, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 3, 3, 93600, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 3, 4, 115200, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 4, 3, 140400, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 4, 4, 172800, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 5, 3, 226200, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (2, 5, 4, 278200, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (3, 1, 2, 50400, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (3, 2, 2, 64800, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (3, 3, 2, 86400, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (3, 4, 2, 129600, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (3, 5, 2, 208800, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (4, 1, 2, 67200, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (4, 2, 2, 86400, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (4, 3, 2, 115200, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (4, 4, 2, 172800, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (4, 5, 2, 278400, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 1, 2, 25000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 1, 3, 32500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 2, 2, 31000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 2, 3, 40300, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 3, 2, 40000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 3, 3, 52000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 4, 2, 65000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 4, 3, 84500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 5, 2, 130000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (5, 5, 3, 169000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 1, 2, 27500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 1, 4, 44000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 2, 2, 34100, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 2, 4, 54560, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 3, 2, 44000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 3, 4, 70400, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 4, 2, 71500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 4, 4, 114400, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 5, 2, 143000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (6, 5, 4, 228800, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (7, 1, 2, 32500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (7, 2, 2, 40300, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (7, 3, 2, 52000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (7, 4, 2, 84500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (7, 5, 2, 169000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (8, 1, 2, 36250, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (8, 2, 2, 45000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (8, 3, 2, 58000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (8, 4, 2, 94250, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (8, 5, 2, 188500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 1, 2, 25000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 1, 3, 32500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 2, 2, 29000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 2, 3, 37700, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 3, 2, 33000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 3, 3, 42900, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 4, 2, 60000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 4, 3, 78000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 5, 2, 110000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (9, 5, 3, 143000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 1, 2, 27500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 1, 3, 35750, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 2, 2, 31900, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 2, 3, 41470, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 3, 2, 36300, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 3, 3, 47190, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 4, 2, 66000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 4, 3, 85800, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 5, 2, 121000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (10, 5, 3, 157300, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 1, 2, 20000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 1, 3, 26000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 2, 2, 22000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 2, 3, 28600, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 3, 2, 27000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 3, 3, 35100, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 4, 2, 55000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 4, 3, 71500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 5, 2, 90000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (11, 5, 3, 117000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 1, 2, 26000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 1, 3, 28600, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 2, 2, 28600, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 2, 3, 31460, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 3, 2, 35100, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 3, 3, 38610, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 4, 2, 71500, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 4, 3, 78650, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 5, 2, 117000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (12, 5, 3, 128700, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 1, 2, 20000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 1, 3, 35200, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 1, 4, 38600, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 2, 2, 22000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 2, 3, 38720, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 2, 4, 42000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 3, 2, 27000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 3, 3, 47520, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 3, 4, 54000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 4, 2, 55000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 4, 3, 96800, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 4, 4, 108000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 5, 2, 90000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 5, 3, 158400, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
INSERT INTO point_scores (ccommodation_id, season_id, pax, points, created_at, updated_at) VALUES (13, 5, 4, 174000, NOW(), NOW()) ON DUPLICATE KEY UPDATE points = VALUES(points);
