# Base image: PostgreSQL 17 with pgvector
FROM pgvector/pgvector:pg17

# Environment variable for the password (optional, can be overridden at runtime)
ENV POSTGRES_PASSWORD=root

# Copy the SQL script to initialize the database
COPY init.sql /docker-entrypoint-initdb.d/