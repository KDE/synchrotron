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

-- DROP TABLE content;
CREATE TABLE content
(
    id          TEXT        NOT NULL,
    provider    INT         NOT NULL REFERENCES providers(id) ON DELETE CASCADE,
    created     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    downloads   INT         NOT NULL DEFAULT 0,
    version     TEXT,
    author      TEXT,
    homepage    TEXT,
    preview     TEXT,
    name        TEXT        NOT NULL, -- FIXME: i18n
    description TEXT, -- FIXME: i18n
    package     TEXT,
    CONSTRAINT content_pk PRIMARY KEY (id, provider)
);

-- DROP TABLE accesscounts;
CREATE TABLE accesses
(
    address     INET        NOT NULL,
    ts          TIMESTAMP   NOT NULL default CURRENT_TIMESTAMP
);
create INDEX idx_accesses on accesses (address, ts);

