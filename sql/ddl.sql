-- DROP TABLE scanning
CREATE TABLE scanning
(
    lastScan    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- drop sequence seq_providerIds;
CREATE SEQUENCE seq_providerIds;

-- drop table providers;
CREATE TABLE providers
(
    id          INT         PRIMARY KEY DEFAULT nextval('seq_providerIds'),
    name        TEXT        NOT NULL UNIQUE,
    typename    TEXT        -- FIXME: i18n
);

-- drop sequence seq_contentIds;
CREATE SEQUENCE seq_contentIds;

-- DROP TABLE content
CREATE TABLE content
(
    id          INT         PRIMARY KEY DEFAULT nextval('seq_providerIds'),
    provider    INT         NOT NULL REFERENCES providers(id) ON DELETE CASCADE,
    created     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    downloads   INT         NOT NULL DEFAULT 0,
    version     TEXT,
    author      TEXT,
    homepage    TEXT,
    preview     TEXT,
    name        TEXT        NOT NULL, -- FIXME: i18n
    description TEXT -- FIXME: i18n
);

