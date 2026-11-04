INSERT INTO users (name, email, phone, password_hash, is_verified)
VALUES (
	'Administrator Demo',
	'admin@test.com',
	'',
	'$2y$12$Xa7hIJZiKwwgfzaP4Fq3d.2idyxxM4gqKWUMudo8ewMxkxl/ApgWq',
	1
)
ON DUPLICATE KEY UPDATE
	name = VALUES(name),
	password_hash = VALUES(password_hash),
	is_verified = 1,
	verification_code_hash = NULL,
	verification_expires = NULL;
