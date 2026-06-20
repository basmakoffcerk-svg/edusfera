# =============================================================================
# Stage 1: Build
# =============================================================================
FROM golang:1.24-alpine AS builder

ENV GOTOOLCHAIN=auto

RUN apk add --no-cache git ca-certificates

WORKDIR /app

# Cache dependencies
COPY go.mod go.sum ./
RUN go mod download

# Copy source and build
COPY . .
RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 \
    go build -ldflags="-s -w" -trimpath -o /media-server ./cmd/server

# =============================================================================
# Stage 2: Runtime
# =============================================================================
FROM alpine:3.21

RUN apk --no-cache add ca-certificates tzdata

RUN addgroup -S app && adduser -S app -G app

COPY --from=builder /media-server /usr/local/bin/media-server

USER app

EXPOSE 8088

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget -qO- http://localhost:8088/health || exit 1

ENTRYPOINT ["media-server"]
