# ⚙️ Kubernetes Documentation

Panduan deploy InvenSys ke Kubernetes cluster.

---

## Prasyarat

- Kubernetes cluster aktif (Docker Desktop, minikube, atau cloud provider)
- `kubectl` terinstall dan terkonfigurasi
- Docker images sudah di-build dan di-push ke registry

---

## Struktur Manifests

```
k8s/
├── configmap.yaml    # Non-secret environment variables
├── secret.yaml       # Credentials (APP_KEY, DB password)
├── deployment.yaml   # Semua Deployment + PVC untuk MySQL
├── service.yaml      # ClusterIP dan NodePort services
└── ingress.yaml      # Ingress rule (opsional)
```

---

## Komponen

| Deployment | Replicas | Image | Fungsi |
|---|---|---|---|
| `invensys-mysql` | 1 | mysql:8.0 | Database |
| `invensys-backend` | 2 | invensys-backend:latest | PHP-FPM Laravel |
| `invensys-nginx` | 2 | invensys-nginx:latest | Web server + SPA |
| `invensys-queue` | 1 | invensys-backend:latest | Queue worker |

---

## Langkah Deploy

### 1. Build & Push Images

```bash
# Build images
docker build --target backend -t your-registry/invensys-backend:latest .
docker build --target nginx   -t your-registry/invensys-nginx:latest .

# Push ke Docker Hub
docker push your-registry/invensys-backend:latest
docker push your-registry/invensys-nginx:latest
```

Update nama image di `k8s/deployment.yaml`.

### 2. Setup Secrets

Edit `k8s/secret.yaml`:

```yaml
stringData:
  APP_KEY: "base64:YOUR_KEY_HERE"    # Ganti!
  DB_PASSWORD: "secure_password"     # Ganti!
  DB_ROOT_PASSWORD: "root_password"  # Ganti!
```

Generate APP_KEY:
```bash
php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

### 3. Apply Manifests

```bash
# Deploy semua
kubectl apply -f k8s/

# Cek status
kubectl get pods
kubectl get services
kubectl get deployments
```

### 4. Jalankan Migration

```bash
# Dapatkan nama pod backend
kubectl get pods -l component=backend

# Exec ke pod
kubectl exec -it <pod-name> -- php artisan migrate --force
kubectl exec -it <pod-name> -- php artisan db:seed --force
```

---

## Akses Aplikasi

**Via NodePort (Docker Desktop / minikube):**
```
http://localhost:30080
```

**Via Ingress (perlu nginx-ingress-controller):**
```bash
# Install ingress controller
kubectl apply -f https://raw.githubusercontent.com/kubernetes/ingress-nginx/main/deploy/static/provider/cloud/deploy.yaml

# Apply ingress
kubectl apply -f k8s/ingress.yaml

# Tambah ke hosts file (Windows: C:\Windows\System32\drivers\etc\hosts)
127.0.0.1 invensys.local
```

Akses: `http://invensys.local`

---

## Services

| Service | Type | Port Internal | Port External |
|---|---|---|---|
| `invensys-mysql` | ClusterIP | 3306 | — |
| `php` | ClusterIP | 9000 | — |
| `invensys-nginx` | NodePort | 80 | 30080 |

---

## Resource Limits

| Container | CPU Req | CPU Limit | Mem Req | Mem Limit |
|---|---|---|---|---|
| MySQL | 250m | 500m | 256Mi | 1Gi |
| Backend | 100m | 500m | 128Mi | 512Mi |
| Nginx | 50m | 200m | 64Mi | 128Mi |
| Queue | 100m | 300m | 128Mi | 256Mi |

---

## Scaling

```bash
# Scale manual
kubectl scale deployment invensys-backend --replicas=3
kubectl scale deployment invensys-nginx --replicas=3

# Horizontal Pod Autoscaler
kubectl autoscale deployment invensys-backend --cpu-percent=70 --min=2 --max=5
```

---

## Rolling Update

```bash
# Build & push image baru
docker build --target backend -t your-registry/invensys-backend:v2 .
docker push your-registry/invensys-backend:v2

# Rolling update
kubectl set image deployment/invensys-backend php=your-registry/invensys-backend:v2

# Status
kubectl rollout status deployment/invensys-backend

# Rollback
kubectl rollout undo deployment/invensys-backend
```

---

## Monitoring

```bash
# Lihat logs
kubectl logs -f deployment/invensys-backend
kubectl logs -f deployment/invensys-nginx

# Describe pod (debugging)
kubectl describe pod <pod-name>

# Resource usage
kubectl top pods
kubectl top nodes
```

---

## Cleanup

```bash
# Hapus semua resource InvenSys
kubectl delete -f k8s/
```

---

## Catatan untuk Docker Desktop

Kubernetes di Docker Desktop menggunakan **kubeadm** cluster bawaan. Untuk mengaktifkan/menonaktifkan:

- **Aktifkan:** Docker Desktop → Settings → Kubernetes → Enable Kubernetes → Apply
- **Nonaktifkan:** Settings → Kubernetes → Stop (cluster berhenti, config & manifest tetap)
- **Reset:** Settings → Kubernetes → Reset Kubernetes Cluster (hapus semua pods/deployments)

Untuk development, **Docker Compose sudah cukup**. Kubernetes relevan untuk simulasi production atau deploy ke cloud.
