--   Copyright 2011 Aaron Seigo <aseigo@kde.org>
--
--   This program is free software; you can redistribute it and/or modify
--   it under the terms of the GNU Library General Public License as
--   published by the Free Software Foundation; either version 2, or
--   (at your option) any later version.
--
--   This program is distributed in the hope that it will be useful,
--   but WITHOUT ANY WARRANTY; without even the implied warranty of
--   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--   GNU General Public License for more details
--
--   You should have received a copy of the GNU Library General Public
--   License along with this program; if not, write to the
--   Free Software Foundation, Inc.,
--   51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

-- DROP TABLE scanning
CREATE TABLE scanning
(
    lastScan    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=INNODB;

-- drop table providers;
CREATE TABLE providers
(
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL UNIQUE,
    typename    TEXT         -- FIXME: i18n
) ENGINE=INNODB;

-- DROP TABLE categories
CREATE TABLE categories
(
    id          INT         AUTO_INCREMENT PRIMARY KEY,
    provider    INT         NOT NULL,
    name        TEXT        NOT NULL, -- FIXME: i18n
    FOREIGN KEY (provider) REFERENCES providers(id) ON DELETE CASCADE
) ENGINE=INNODB;

-- DROP TABLE content;
CREATE TABLE content
(
    id          VARCHAR(100) NOT NULL,
    provider    INT          NOT NULL,
    category    INT,
    created     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated     TIMESTAMP    NOT NULL,
    downloads   INT          NOT NULL DEFAULT 0,
    version     TEXT,
    author      TEXT,
    homepage    TEXT,
    preview     TEXT,
    name        TEXT         NOT NULL, -- FIXME: i18n
    description TEXT, -- FIXME: i18n
    package     TEXT,
    externalPackage     BOOL    NOT NULL DEFAULT FALSE,
    CONSTRAINT content_pk PRIMARY KEY (id, provider),
    FOREIGN KEY (provider) REFERENCES providers(id) ON DELETE CASCADE,
    FOREIGN KEY (category) REFERENCES providers(id) ON DELETE SET NULL
) ENGINE=INNODB;

-- DROP TABLE accesscounts;
CREATE TABLE accesses
(
    address     VARCHAR(255) NOT NULL,
    ts          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=INNODB;
CREATE INDEX idx_accesses on accesses (address, ts);

GRANT SELECT ON providers TO synchrotron_ro;
GRANT SELECT ON content TO synchrotron_ro;
GRANT INSERT,SELECT ON accesses TO synchrotron_ro;

