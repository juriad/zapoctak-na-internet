CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    active INTEGER NOT NULL, --boolean
    added INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP,
    password TEXT NOT NULL,
    lastLogged INTEGER --null = never
);

CREATE TABLE user_roles (
    userId NOT NULL REFERENCES users (id),
    role TEXT
);

CREATE TABLE scans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    time INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE files (
    id INTEGER PRIMARY KEY AUTOINCREMENT, --key for comment
    version INTEGER NOT NULL, --keeps current maximum, managed by trigger

    name TEXT NOT NULL, --using like
    isDirectory INTEGER NOT NULL, --boolean
    isDeleted INTEGER NOT NULL, --boolean, select only existing
    inode INTEGER NOT NULL, --tracking movements
    modified INTEGER NOT NULL,
    length INTEGER NOT NULL,
    scanId INTEGER NOT NULL REFERENCES scans (id),
    mime TEXT NOT NULL,

    parent INTEGER REFERENCES files (id), --select files for dir
    path TEXT NOT NULL DEFAULT '/',
    realPath TEXT NOT NULL
    -- before update trigger moves to history
    -- after update trigger increases version and refreshes lastScanUpdate
);

CREATE TRIGGER insertNewFilePath AFTER INSERT ON files
    BEGIN
        UPDATE files
            SET path = CASE
                WHEN new.parent IS NULL THEN '/' ELSE (SELECT f.path FROM files f WHERE f.id = new.parent ) END || new.id || '/'
            WHERE id = new.id;
    END;

CREATE TRIGGER moveFileToHistory BEFORE UPDATE ON files 
    WHEN new.scanId != old.scanId --do not trigger when moving or increasing
    BEGIN
        INSERT INTO history (fileId, version, name, modified, scanId, length, mime, parent, path, realPath)
            VALUES (old.id, old.version, old.name, old.modified, old.scanId, old.length, old.mime, old.parent, old.path, old.realPath);
    END;

CREATE TRIGGER increaseVersion AFTER UPDATE ON files
    WHEN new.scanId != old.scanId --do not trigger when moving
    BEGIN
        UPDATE files SET version = version+1 WHERE id = new.id;
    END;

CREATE TRIGGER rebuildPath AFTER UPDATE ON files
    WHEN new.parent IS NOT old.parent
    BEGIN
        UPDATE files SET
            path = (SELECT path FROM files WHERE id = new.parent) || 
                substr( (SELECT f.path FROM files f WHERE f.id = files.id ), 
                    (SELECT length(path)+1 FROM files WHERE id = old.parent))
            WHERE path GLOB ( old.path || '*');
    END;

CREATE INDEX filesDeleted ON files (isDeleted);
CREATE INDEX filesInode ON files (inode);
CREATE INDEX filesPath ON files (path);

CREATE TABLE history (
    id INTEGER PRIMARY KEY AUTOINCREMENT, -- key for comment
    fileId INTEGER NOT NULL REFERENCES files (id), --select history for file
    version INTEGER NOT NULL,

    name TEXT NOT NULL, --using like
    scanId INTEGER NOT NULL REFERENCES scans (id),
    modified INTEGER NOT NULL,
    length INTEGER NOT NULL,
    mime TEXT NOT NULL,

    parent INTEGER REFERENCES files (id),
    path TEXT NOT NULL,
    realPath TEXT NOT NULL
);

CREATE INDEX historyFileId ON history (fileId);
CREATE INDEX historyParent ON history (parent);

CREATE TABLE roots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    name TEXT NOT NULL UNIQUE,
    path TEXT NOT NULL,
    state INTEGER NOT NULL, -- 0=disabled, 1=enabled, -1, -2=error
    lastScanId INTEGER REFERENCES scans (id),
    scanInterval INTEGER NOT NULL,
    added INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP,

    rootFile INTEGER REFERENCES files (id)
);

CREATE TABLE user_roots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    userId INTEGER NOT NULL REFERENCES users (id),
    rootId INTEGER NOT NULL REFERENCES roots (id),
    active INTEGER NOT NULL,
    added INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT userRoleMap UNIQUE (userId, rootId) ON CONFLICT IGNORE
);

CREATE VIRTUAL TABLE commentBodies USING fts4 (
    id INTEGER PRIMARY KEY REFERENCES comments (id) ON DELETE CASCADE,
    body TEXT NOT NULL
);

CREATE TABLE comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    added INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP,
    userId INTEGER NOT NULL REFERENCES users (id),

--    parent INTEGER REFERENCES comments (id) ON DELETE CASCADE,
    targetId INTEGER NOT NULL,
    targetTable TEXT NOT NULL
--    level INTEGER NOT NULL DEFAULT 0 --managed by trigger
);

--CREATE TRIGGER increaseLevel AFTER INSERT ON comments
--    WHEN new.parent IS NOT NULL
--    BEGIN
--        UPDATE comments SET level = (SELECT level+1 FROM comments WHERE id = new.parent);
--    END;


--CREATE INDEX commentsParent ON comments (parent);
CREATE INDEX commentsTargetId ON comments (targetId);
CREATE INDEX commentsTargetTable ON comments (targetTable);

CREATE TABLE tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);

CREATE TABLE files_tags (
    fileId INTEGER NOT NULL REFERENCES files (id),
    tagId INTEGER NOT NULL REFERENCES tags (id),
    added INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP,
    userId INTEGER NOT NULL REFERENCES users (id),
    CONSTRAINT filesTagsMap UNIQUE (fileId, tagId) ON CONFLICT IGNORE
);

CREATE INDEX files_tagsFileId ON files_tags (fileId);
CREATE INDEX files_tagsTagId ON files_tags (tagId);

CREATE TABLE metadata (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE, --ex: thumbnail
    type TEXT NOT NULL, --ex: image
    externalFile INTEGER NOT NULL --boolean
);

CREATE TABLE files_metadata (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fileId INTEGER NOT NULL, --references
    version INTEGER NOT NULL, -- references
    metadataId INTEGER NOT NULL REFERENCES metadata (id),
    fileName TEXT
);

CREATE INDEX files_metadataFileId ON files_metadata (fileId);

CREATE VIRTUAL TABLE textData USING fts4 (
    id INTEGER PRIMARY KEY REFERENCES files_metadata (id),
    content TEXT NOT NULL,
    wrap INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE routines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    comment TEXT NOT NULL,
    metadataId INTEGER NOT NULL REFERENCES metadata (id)
);

CREATE TABLE rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    userId INTEGER NOT NULL REFERENCES users (id),

    name TEXT NOT NULL,
    criterion TEXT NOT NULL,
    value TEXT NOT NULL,
    routineId INTEGER NOT NULL REFERENCES routines (id),
    added INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lastScanId INTEGER REFERENCES scans (id),

    fileId INTEGER NOT NULL REFERENCES files (id),
    subdirectories INTEGER NOT NULL --boolean
);

CREATE INDEX rulesUserId ON rules (userId);
CREATE INDEX rulesFileId ON rules (fileId);

CREATE TABLE errors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT,
    message TEXT,
    data TEXT,
    time INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX errorsAction ON errors (action);
