# Guía de integración API SISPAM — Creación de pacientes

**Versión:** 1.1  
**Audiencia:** proveedor externo de integración (servicio SISPAM)  
**Ámbito:** alta y actualización de pacientes (afiliados) desde un sistema de servicio

> Este documento describe únicamente el contrato público de la API.  
> Las credenciales, URL de ambiente, códigos de sede, planes, aseguradoras y catálogos se entregan por canal seguro interno (no van en este archivo).

---

## 1. Resumen

Todas las operaciones SISPAM se invocan con un único endpoint JSON:

| Ítem | Valor |
|------|--------|
| Método HTTP | `POST` |
| Ruta | `/api/json/` |
| Content-Type | `application/json` |
| Modelo fijo | `SISPAM` |
| Autenticación | **Basic Auth** |

### Métodos disponibles

| # | Método | Descripción |
|---|--------|-------------|
| 1 | `INSERTAR` | Crea un paciente. Si el tipo y número de documento ya existen, **actualiza** los datos enviados (upsert) |
| 2 | `EDITAR` | Actualiza un paciente existente (por `IDAFILIADO` o por tipo + documento) |

El método de negocio para el alta desde servicio es **`INSERTAR`**.

---

## 2. Autenticación

Este contrato usa **HTTP Basic Authentication**.  
Usuario y clave de integración se **comparten por canal interno** (onboarding). No se publican en este documento.

**Header:**

```http
Authorization: Basic {BASE64(usuario:clave)}
Content-Type: application/json
```

**Ejemplo de generación del token Base64** (ilustrativo, no es una credencial real):

```text
usuario:clave  →  Base64  →  dXN1YXJpbzpjbGF2ZQ==
```

```http
Authorization: Basic dXN1YXJpbzpjbGF2ZQ==
```

**Notas:**

- En cada request a `/api/json/` envíe el body JSON completo (no se requiere login previo).
- El usuario de integración debe tener habilitado el modelo `SISPAM` y los métodos `INSERTAR` / `EDITAR`.
- No use JWT (`Bearer`) en este contrato.
- No registre usuario, clave ni el header `Authorization` en logs públicos.
- Use HTTPS siempre.

La URL base se informa en el onboarding (ejemplo genérico: `https://{HOST}`).

---

## 3. Contrato común de `/api/json/`

### Request

```json
{
  "MODELO": "SISPAM",
  "METODO": "INSERTAR",
  "USUARIO": "{USUARIO}",
  "PARAMETROS": { }
}
```

| Campo | Obligatorio | Descripción |
|-------|-------------|-------------|
| `MODELO` | **Sí** | Siempre `SISPAM` |
| `METODO` | **Sí** | `INSERTAR` o `EDITAR` |
| `USUARIO` | **Sí** | Usuario de integración acordado en el onboarding (auditoría del alta/edición) |
| `PARAMETROS` | **Sí** | Objeto con los datos del paciente |

Cualquier otro `METODO` responde `KO` con el mensaje: `Metodo no soportado en SPQ_SISPAM`.

### Response

La respuesta HTTP `200` con `res: "ok"` indica que el request llegó al backend. El resultado de negocio viene en `result` (uno o más `recordsets`).

**Convención de negocio:**

| Campo | Significado |
|-------|-------------|
| `OK = "OK"` | Operación exitosa |
| `OK = "KO"` | Error de validación o negocio; revise el siguiente resultset `ERROR` |

Ejemplo de error de negocio:

```json
{
  "res": "ok",
  "result": {
    "recordsets": [
      [{ "OK": "KO", "CONSECUTIVO": "" }],
      [{ "ERROR": "Seleccione un Tipo de Documento" }]
    ]
  }
}
```

Puede haber **varios** mensajes `ERROR` en el segundo resultset. Corrija todos antes de reenviar.

---

## 4. Flujo sugerido de integración

```text
1. Autenticar con Basic Auth (credenciales internas).
2. Armar PARAMETROS con los catálogos entregados en onboarding
   (tipo de documento, ciudad DIVIPOLA, barrio, aseguradora, plan, sede, etc.).
3. POST INSERTAR.
4. Si OK + ACCION = CREADO     → paciente nuevo (CONSECUTIVO = ID interno).
   Si OK + ACCION = ACTUALIZADO → el documento ya existía; se actualizaron los campos enviados.
   Si KO                        → leer ERROR y corregir el payload.
```

No reintente el mismo alta ante un `KO` de negocio (documento inválido, plan no incluido, etc.).  
Sí puede reintentar ante errores de red o HTTP `5xx`.

---

## 5. Métodos detallados

### 5.1 `INSERTAR`

Crea un paciente. Si ya existe un afiliado con el mismo `TIPO_DOC` + `DOCIDAFILIADO` (distinto de `AS`/`MS`), el sistema **no rechaza** el request: pasa a actualización y responde `EXISTE = 1`, `ACCION = ACTUALIZADO`.

#### Parámetros (`PARAMETROS`)

Los valores de ejemplo son de **pruebas**. Aseguradora, plan, sede, ciudad y barrio reales se confirman en el onboarding.

##### Obligatorios

| Parámetro | Obligatorio | Tipo | Long. máx. | Descripción | Ejemplo |
|-----------|-------------|------|------------|-------------|---------|
| `TIPO_DOC` | **Sí** | string | 3 | Tipo de documento. Catálogo de onboarding (ej. `CC`, `TI`, `RC`, `CE`, `PA`) | `"CC"` |
| `DOCIDAFILIADO` | **Sí** | string | 20 | Número de documento. Solo `0-9` y `A-Z`; más de 3 caracteres | `"9900990099"` |
| `FNACIMIENTO` | **Sí** | date | — | Fecha de nacimiento `YYYY-MM-DD`. Debe ser coherente con `TIPO_DOC` (ver §6) | `"1990-05-15"` |
| `PAPELLIDO` | **Sí** | string | 30 | Primer apellido. Más de 2 caracteres | `"PRUEBA"` |
| `PNOMBRE` | **Sí** | string | 30 | Primer nombre. Más de 2 caracteres | `"PACIENTE"` |
| `SEXO` | **Sí** | string | 9 | Sexo. Valores del catálogo (ej. `Masculino`, `Femenino`) | `"Masculino"` |
| `ESTADO_CIVIL` | **Sí** | string | 15 | Estado civil. Catálogo de onboarding (ej. `Soltero`) | `"Soltero"` |
| `GRUPOPOB` | **Sí** | string | 20 | Grupo poblacional. Catálogo de onboarding | `"5"` |
| `GRUPOETNICO` | **Sí** | string | 1 | Grupo étnico. Catálogo de onboarding (ej. `N`) | `"N"` |
| `TIPODISCAPACIDAD` | **Sí** | string | 1 | Tipo de discapacidad. Catálogo de onboarding (ej. `N`) | `"N"` |
| `IDESCOLARIDAD` | **Sí** | string | 3 | Escolaridad. Catálogo de onboarding (ej. `NA`) | `"NA"` |
| `DIRECCION` | **Sí** | string | 150 | Dirección de residencia | `"CALLE 1 # 2-3"` |
| `CELULAR` | **Sí** | string | 20 | Celular. Solo dígitos (sin letras) | `"3001112233"` |
| `EMAIL` | **Sí** | string | 99 | Correo electrónico | `"sispam.prueba@test.com"` |
| `CIUDAD` | **Sí** | string | 5 | Municipio de residencia (código DIVIPOLA). Catálogo de onboarding | `"05440"` |
| `ZONA` | **Sí** | string | 12 | Zona. Catálogo de onboarding (`U` urbana / `R` rural, según cliente) | `"U"` |
| `IDBARRIO` | **Sí** | string | 20 | Barrio. Debe corresponder a `CIUDAD`. Catálogo de onboarding | `"05440001"` |
| `IDADMINISTRADORA` | **Sí** | string | 20 | Aseguradora (tercero). Debe coincidir con el catálogo de onboarding | `"0100000010"` |
| `IDPLAN` | **Sí** | string | 6 | Plan de salud. Debe estar **incluido** en la aseguradora y **disponible** en la sede | `"TARC26"` |
| `NIVELSOCIOEC` | **Sí** | string | 2 | Nivel socioeconómico. Catálogo de onboarding | `"2"` |
| `TIPOUSUARIO` | **Sí** | string | 20 | Tipo de afiliado. Debe ser compatible con la cobertura del plan | `"C"` |
| `IDSEDE` | **Sí** | string | 10 | Sede de creación. Catálogo de onboarding | `"29"` |
| `ESTADO` | **Sí** | string | 12 | Estado del paciente. En el alta envíe `Activo` | `"Activo"` |
| `FECHAAFILIACION` | Condicional | date | — | Fecha de ingreso `YYYY-MM-DD`. **Obligatoria** solo si el plan es habitante de calle. No puede ser futura | `"2026-08-25"` |

##### Opcionales

| Parámetro | Obligatorio | Tipo | Long. máx. | Descripción | Ejemplo |
|-----------|-------------|------|------------|-------------|---------|
| `SAPELLIDO` | No | string | 30 | Segundo apellido | `"SISPAM"` |
| `SNOMBRE` | No | string | 30 | Segundo nombre | `"UNO"` |
| `PREFIJO_CELULAR` | No | string | 20 | Indicativo del celular (ej. `+57`) | `"+57"` |
| `TELEFONORES` | No | string | 15 | Teléfono fijo / residencia. Si se envía, no puede contener letras. Cadena vacía `""` equivale a no enviar | `""` |
| `PREFIJO_TELEFONORES` | No | string | 20 | Indicativo del teléfono de residencia (ej. `+57`) | `"+57"` |
| `PROCEDENCIA` | No | string | 20 | Origen del registro. Se recomienda `SISPAM` | `"SISPAM"` |

\* El identificador interno (`IDAFILIADO` / `CONSECUTIVO`) lo genera el sistema en el alta. No es necesario enviarlo en `INSERTAR`.

#### Ejemplo de alta

`USUARIO` es el de integración acordado internamente (no es usuario ni clave de Basic Auth).

```json
{
  "MODELO": "SISPAM",
  "METODO": "INSERTAR",
  "USUARIO": "{USUARIO}",
  "PARAMETROS": {
    "TIPO_DOC": "CC",
    "DOCIDAFILIADO": "9900990099",
    "FNACIMIENTO": "1990-05-15",
    "PAPELLIDO": "PRUEBA",
    "PNOMBRE": "PACIENTE",
    "SEXO": "Masculino",
    "ESTADO_CIVIL": "Soltero",
    "GRUPOPOB": "5",
    "GRUPOETNICO": "N",
    "TIPODISCAPACIDAD": "N",
    "IDESCOLARIDAD": "NA",
    "DIRECCION": "CALLE 1 # 2-3",
    "CELULAR": "3001112233",
    "EMAIL": "sispam.prueba@test.com",
    "CIUDAD": "05440",
    "ZONA": "U",
    "IDBARRIO": "05440001",
    "IDADMINISTRADORA": "0100000010",
    "IDPLAN": "TARC26",
    "NIVELSOCIOEC": "2",
    "TIPOUSUARIO": "C",
    "IDSEDE": "29",
    "ESTADO": "Activo",
    "FECHAAFILIACION": "2026-08-25",
    "SAPELLIDO": "SISPAM",
    "SNOMBRE": "UNO",
    "PREFIJO_CELULAR": "+57",
    "TELEFONORES": "",
    "PREFIJO_TELEFONORES": "+57",
    "PROCEDENCIA": "SISPAM"
  }
}
```

`FECHAAFILIACION` es **obligatoria** solo cuando el plan corresponde a habitante de calle (catálogo interno `HABITANTES_CALLE`). En el resto de planes puede omitirse.

#### Respuesta exitosa — paciente nuevo

```json
{
  "res": "ok",
  "result": {
    "recordsets": [
      [
        {
          "OK": "OK",
          "CONSECUTIVO": "{IDAFILIADO}",
          "EXISTE": 0,
          "ACCION": "CREADO",
          "MENSAJE": "Afiliado creado correctamente."
        }
      ]
    ]
  }
}
```

| Campo | Descripción |
|-------|-------------|
| `CONSECUTIVO` | Identificador interno del paciente (`IDAFILIADO`). Consérvelo para ediciones posteriores |
| `EXISTE` | `0` = no existía; se creó |
| `ACCION` | `CREADO` |
| `MENSAJE` | Texto descriptivo |

#### Respuesta exitosa — documento ya existía (upsert)

```json
{
  "res": "ok",
  "result": {
    "recordsets": [
      [
        {
          "OK": "OK",
          "CONSECUTIVO": "{IDAFILIADO}",
          "EXISTE": 1,
          "ACCION": "ACTUALIZADO",
          "MENSAJE": "El afiliado con documento 9900990099 ya existe. Se actualizaron los datos enviados."
        }
      ]
    ]
  }
}
```

En upsert **solo se actualizan los campos presentes** en `PARAMETROS`. No es necesario reenviar el formulario completo.

---

### 5.2 `EDITAR`

Actualiza un paciente que ya existe.

#### Parámetros adicionales respecto a `INSERTAR`

| Parámetro | Obligatorio | Tipo | Descripción |
|-----------|-------------|------|-------------|
| `IDAFILIADO` | **Sí*** | string | Identificador interno (`CONSECUTIVO` de un alta previa) |

\* Si no envía `IDAFILIADO`, el sistema busca por `TIPO_DOC` + `DOCIDAFILIADO` y actualiza ese registro.

Los mismos campos de `INSERTAR` aplican. En edición, los campos **no enviados** no se borran.

Si el paciente no existe: `KO` — `El Paciente NO Existe en la Base de Datos`.

#### Ejemplo

```json
{
  "MODELO": "SISPAM",
  "METODO": "EDITAR",
  "USUARIO": "{USUARIO}",
  "PARAMETROS": {
    "IDAFILIADO": "{IDAFILIADO}",
    "CELULAR": "3009998877",
    "EMAIL": "sispam.actualizado@test.com",
    "DIRECCION": "CALLE 10 # 20-30"
  }
}
```

---

## 6. Reglas de negocio (validaciones)

El backend valida antes de persistir. Cualquier incumplimiento responde `KO` + `ERROR`.

### 6.1 Documento y nombres

- `DOCIDAFILIADO` se normaliza a caracteres `0-9` y `A-Z`.
- Longitud mínima 4 caracteres (salvo pacientes sin identificación `AS`/`MS`, que no aplican a este flujo de servicio).
- `PAPELLIDO` y `PNOMBRE`: más de 2 caracteres.
- Nombres y apellidos se guardan en mayúsculas.

### 6.2 Edad vs tipo de documento

Si se envía `FNACIMIENTO`:

| `TIPO_DOC` | Regla |
|------------|--------|
| `CC` | Edad ≥ 18 años |
| `TI` | Edad entre 7 y 18 años |
| `RC` | Edad ≤ 10 años |

Si no cumple: `La edad del Afiliado no corresponde con el Tipo de Documento`.

Algunos tipos de documento exigen una longitud exacta (catálogo interno). Si no coincide: error de cantidad de caracteres.

### 6.3 Teléfono y correo

- `CELULAR` es obligatorio. No puede contener letras.
- `PREFIJO_CELULAR` es opcional (ej. `+57`).
- `TELEFONORES` es opcional. Si se envía con valor, no puede contener letras. `""` se trata como no enviado.
- `PREFIJO_TELEFONORES` es opcional (ej. `+57`).
- `EMAIL` es obligatorio en el alta.

### 6.4 Aseguradora, plan y sede

- El par `IDADMINISTRADORA` + `IDPLAN` debe existir (plan **incluido** en la aseguradora). Si no: `Plan NO incluido en la Aseguradora`.
- Si el plan está restringido por sede, `IDSEDE` debe estar activa para ese plan. Si no: `Plan no disponible para la sede`.
- `TIPOUSUARIO` debe ser compatible con la cobertura del plan. El mensaje de error lo indica el backend.

Use únicamente códigos entregados en el onboarding.

### 6.5 Fecha de ingreso

- `FECHAAFILIACION` no puede ser mayor a la fecha actual.
- Si el plan es **habitante de calle**, `FECHAAFILIACION` es **obligatoria**.

### 6.6 Pacientes sin identificación (`AS` / `MS`)

No forman parte del flujo estándar SISPAM de servicio. Si se requieren, se acuerda un anexo de onboarding (edad estimada, grupo poblacional y generación de documento).

---

## 7. Códigos HTTP frecuentes

| HTTP | Significado |
|------|-------------|
| `200` | Request procesado (revise `OK`/`KO` en el cuerpo) |
| `400` | Body inválido / JSON mal formado |
| `401` | No autenticado (Basic Auth ausente, inválido o vencido) |
| `403` | Autenticado pero sin permiso para el modelo `SISPAM` o el método |
| `500` | Error interno |

Un HTTP `200` **no** implica éxito de negocio: siempre lea `OK` / `KO`.

---

## 8. Errores de negocio frecuentes

| Mensaje (extracto) | Qué revisar |
|--------------------|-------------|
| `Seleccione un Tipo de Documento` | Falta `TIPO_DOC` |
| `Ingrese el Documento de Identidad` | Falta `DOCIDAFILIADO` o tiene 3 caracteres o menos |
| `Ingrese una fecha de Nacimiento valida` | Falta `FNACIMIENTO` |
| `Ingrese el Primer Apellido` / `Primer Nombre` | Faltan o tienen 2 caracteres o menos |
| `Seleccione el sexo del paciente` | Falta `SEXO` |
| `Seleccione el estado civil del paciente` | Falta `ESTADO_CIVIL` |
| `Seleccione un Grupo poblacional` / `Grupo Etnico` / `Tipo de Discapacidad` / `Escolaridad` | Catálogos vacíos o no enviados |
| `Ingrese la direccion del paciente` | Falta `DIRECCION` |
| `Ingrese el numero de celular del paciente` | Falta `CELULAR` |
| `Validar que el campo de Telefono no tenga Letras` | `CELULAR` con letras |
| `Ingrese un E-mail` | Falta `EMAIL` |
| `Seleccione la Ciudad de residencia` | Falta `CIUDAD` |
| `Seleccione una Zona` | Falta `ZONA` |
| `Seleccione un Barrio` | Falta `IDBARRIO` |
| `Seleccione una Aseguradora` | Falta `IDADMINISTRADORA` |
| `Seleccione un Plan de Salud` | Falta `IDPLAN` |
| `Seleccione un Nivel Socio Economico` | Falta `NIVELSOCIOEC` |
| `Seleccione un Tipo de Afiliado` | Falta `TIPOUSUARIO` |
| `Seleccione una Sede` | Falta `IDSEDE` |
| `Validar que el campo de Telefono no tenga Letras` (teléfono residencia) | `TELEFONORES` con letras |
| `Plan NO incluido en la Aseguradora` | El plan no está asociado a esa aseguradora |
| `Plan no disponible para la sede` | El plan no aplica a `IDSEDE` |
| `La fecha de Ingreso no puede ser mayor a la fecha actual` | `FECHAAFILIACION` futura |
| `El plan ingresado, obliga a tener Fecha de Ingreso` | Plan habitante de calle sin `FECHAAFILIACION` |
| `La edad del Afiliado no corresponde con el Tipo de Documento` | Edad vs `CC`/`TI`/`RC` |
| `El Paciente NO Existe en la Base de Datos` | `EDITAR` con `IDAFILIADO` inexistente |
| `Metodo no soportado en SPQ_SISPAM` | `METODO` distinto de `INSERTAR` / `EDITAR` |
| `Json: Formato Erroneo` | Body no es JSON válido |

---

## 9. Buenas prácticas

1. **No hardcodee** sedes, planes, aseguradoras, ciudades ni barrios: use el paquete de catálogos del onboarding.
2. Envíe fechas en `YYYY-MM-DD`.
3. Trate `KO` como error funcional y muestre el `ERROR` al operador o al log de integración.
4. Conserve `CONSECUTIVO` (`IDAFILIADO`) de cada alta para trazabilidad y para `EDITAR`.
5. Un segundo `INSERTAR` con el mismo documento **actualiza**; no crea un duplicado.
6. No registre tokens, contraseñas ni documentos completos en logs abiertos.
7. Use HTTPS siempre.
8. Reintente solo ante red/`5xx`, no ante `KO` de negocio.

---

## 10. Checklist de onboarding (canal interno)

- [ ] URL base del ambiente (pruebas / producción)
- [ ] Credenciales **Basic Auth** (usuario y clave de integración)
- [ ] Habilitación del modelo `SISPAM` y métodos `INSERTAR` / `EDITAR`
- [ ] Valor de `USUARIO` a enviar en el body
- [ ] Catálogo de tipos de documento (`TIPO_DOC`)
- [ ] Catálogo de sexo y estado civil
- [ ] Catálogo de grupo poblacional, étnico, discapacidad y escolaridad
- [ ] Catálogo de zona, nivel socioeconómico y tipo de afiliado (`TIPOUSUARIO`)
- [ ] Códigos de sede (`IDSEDE`)
- [ ] Códigos de aseguradora (`IDADMINISTRADORA`) y planes (`IDPLAN`) vigentes, incluida la marca de planes habitante de calle
- [ ] Códigos DIVIPOLA de ciudad (`CIUDAD`) y barrios (`IDBARRIO`)
- [ ] Ventanas horarias / reglas adicionales del cliente

---

## 11. Contacto

Para habilitación de usuario, lista blanca de métodos, catálogos o soporte de integración, contacte al canal técnico acordado en el proceso de onboarding interno.

---

*Documento público de integración. No incluye secretos, cadenas de conexión, ni detalles internos de infraestructura.*
