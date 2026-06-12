FROM postgres:16

# Install build dependencies for Apache AGE
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    postgresql-server-dev-16 \
    curl \
    ca-certificates \
    libreadline-dev \
    zlib1g-dev \
    flex \
    bison \
    git \
    && rm -rf /var/lib/apt/lists/*

# Build and Install Apache AGE using the PG16 branch
RUN curl -L https://github.com/apache/age/archive/refs/heads/PG16.tar.gz | tar -xz -C /tmp \
    && cd /tmp/age-PG16 \
    && make \
    && make install \
    && rm -rf /tmp/age-PG16

# Cleanup build dependencies to keep image small
RUN apt-get purge -y --auto-remove \
    build-essential \
    postgresql-server-dev-16 \
    libreadline-dev \
    zlib1g-dev \
    flex \
    bison \
    git \
    curl

# Ensure postgresql extension dir is prepared
# Apache AGE install usually handles this.
