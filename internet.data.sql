INSERT INTO users (id, name, active, password) VALUES (1, "adam", 1, "9d4e1e23bd5b727046a9e3b4b7db57bd8d6ee684"); -- password: 'pass'
--INSERT INTO users (id, name, active, password) VALUES (2, "pavel", 1, "9d4e1e23bd5b727046a9e3b4b7db57bd8d6ee684"); -- password: 'pass'

INSERT INTO user_roles (userId, role) VALUES (1, 'users');
INSERT INTO user_roles (userId, role) VALUES (1, 'roots');

INSERT INTO metadata (id, name, type, externalFile) VALUES (1, "backup", "file", 1);
INSERT INTO metadata (id, name, type, externalFile) VALUES (2, "thumbnail", "image", 1);
INSERT INTO metadata (id, name, type, externalFile) VALUES (3, "text-preview", "text", 0);
INSERT INTO metadata (id, name, type, externalFile) VALUES (4, "content", "text", 0);

INSERT INTO routines (id, name, comment, metadataId) VALUES (1, "backup-file", "Backup file of each version, you can download each of them later", 1);
INSERT INTO routines (id, name, comment, metadataId) VALUES (2, "image-thumbnail", "Create a small preview of this image", 2);
INSERT INTO routines (id, name, comment, metadataId) VALUES (3, "video-thumbnail", "Create an image preview of this video", 2);
INSERT INTO routines (id, name, comment, metadataId) VALUES (4, "document-preview", "Transform document to plain text", 3);
INSERT INTO routines (id, name, comment, metadataId) VALUES (5, "spreadsheet-preview", "Transform spreadsheet to ascii table", 3);
INSERT INTO routines (id, name, comment, metadataId) VALUES (6, "text-content", "Backup text file and include it here", 4);

--INSERT INTO roots (name, state, scanInterval, path) VALUES ("videa", 1, 3600, "/home/adam/Videa");
--INSERT INTO roots (name, state, scanInterval, path) VALUES ("obrazky", 1, 3600, "/home/adam/Obrázky");
