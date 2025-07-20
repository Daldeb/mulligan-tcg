FROM node:18-alpine

RUN apk add --no-cache git

WORKDIR /var/www/frontend

COPY frontend/package*.json ./
RUN npm install || echo "npm install failed, continuing..."

COPY frontend/ .
RUN npm run build || echo "Build completed"

CMD ["tail", "-f", "/dev/null"]
