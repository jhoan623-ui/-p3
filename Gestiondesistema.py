# sistema de gestion 

class Empleado:
    contador_id = 1 

    def __init__(self, nombre, salario_base):
        self.nombre = nombre
        self.id_empleado = Empleado.contador_id
        Empleado.contador_id += 1
        self.salario_base = salario_base
        self.proyectos = []
        self.maximo_proyectos = 0
    
    def calcular_salario(self):
        return self.salario_base
    
    def mostrar_informacion(self):
        print(f"Nombre: {self.nombre}")
        print(f"ID: {self.id_empleado}")
        print(f"Salario: {self.calcular_salario():.2f}")
        print(f"Proyectos activos: { len(self.proyectos)}")

    def asignar_proyecto(self,proyecto):
        if proyecto in self.proyectos:
            print(f"{self.nombre} ya esta en el proyecto {proyecto.nombre}")
            return
        if len(self.proyectos) >= self.maximo_proyectos:
            print(f"{self.nombre} alcanzo limite de {self.maximo_proyectos} proyectos")
            return
        self.proyectos.append(proyecto)
        proyecto.agregar_empleado(self)

class Desarrollador(Empleado):
    def __init__(self, nombre, salario_base, lenguajes, nivel):
        super().__init__(nombre, salario_base)
        self.lenguajes = lenguajes
        self.nivel = nivel
        self.maximo_proyectos = 3
    
    def calcular_salario(self):
        bonos = {"junior": 200, "semisenior": 500, "senior": 1000}
        return self.salario_base + bonos.get(self.nivel, 0)
    
class Diseñador(Empleado):
    def __init__(self, nombre, salario_base, herramientas, especialidad):
        super().__init__(nombre, salario_base)
        self.herramientas = herramientas
        self.especialidad = especialidad
        self.maximo_proyectos = 2

    def calcular_salario(self):
        base = self.salario_base
        if "Figma" in self. herramientas:
            base += 300
        elif set(self.herramientas).issubset({"photoshop", "Illustrator"}):
            base += 200
        if len(self.herramientas) >= 3:
            base += 400
        return base
    
class Gerente(Empleado):
    def __init__(self, nombre, salario_base, departamento):
        super().__init__(nombre, salario_base)
        self.departamento = departamento
        self.equipo = []
        self.maximo_proyectos = 0

    def calcular_salario(self):
        total_equipo = sum(e.calcular_salario() for e in self.equipo)
        return self.salario_base + 0.15* total_equipo
    
    def agregar_al_equipo(self, empleado):
        if isinstance(empleado, (Desarrollador, Diseñador)):
            self.equipo.append(empleado)
        else:
            print("solo se pueden agregar desarrolladores o diseñadores al equipo del gerente.")
    
    def asignar_proyecto(self, proyecto):
        print ("Un gerente no puede ser miembro de proyectos ( solo responsble)")

class Proyecto:
    def __init__(self, nombre, presupuesto, responsable = None):
        self.nombre = nombre
        self.presupuesto = presupuesto
        self.empleados = []
        self.responsable = responsable 
    
    def agregar_empleado(self, empleado):
        if empleado in self.empleados:
            print(f"El empleado {empleado.nombre} se encuentra en {self.nombre}")
            return
        self.empleados.append(empleado)
    
    def costo_total(self):
        return sum(e.calcular_salario() for e in self.empleados)
    
    def viabilidad (self):
        return self.costo_total() <= self.presupuesto * 0.7
    
if __name__ == "__main__":
    # 1)crear 1 gerente con 2 desarrolladores y 1 diseñador
    gerente = Gerente("Andres", 3200, "I+D")
    dev1 = Desarrollador("Leonor", 2500, ["C++", "HP"], "senior")
    dev2 = Desarrollador("Zulai", 1500, ["C", "SQL"], "semisenior")
    dis1 = Diseñador("Ana",1700, ["Picell", "Imagen","Canva"], "XI")

    gerente.agregar_al_equipo(dev1)
    gerente.agregar_al_equipo(dev2)
    gerente.agregar_al_equipo(dis1)

    # 2) Crear 2 proyectos con presupuestos definidos
    proyecto_a = Proyecto("Paginas Web", 20000, responsable = gerente)
    proyecto_b = Proyecto("Movilnet", 30000, responsable=gerente)

    # 3) Asignar empleados respetando limites
    dev1.asignar_proyecto(proyecto_a)
    dev2.asignar_proyecto(proyecto_a)
    dis1.asignar_proyecto(proyecto_a)

    dev1.asignar_proyecto(proyecto_b)
    dev2.asignar_proyecto(proyecto_b)
    dis1.asignar_proyecto(proyecto_b)

    # 4) Mostramos viabilidad
    print ("\n== Viabilidad de proyectos ==")
    print(f"{proyecto_a.nombre}: costo= ${proyecto_a.costo_total():.2f} | Viable = {proyecto_a.viabilidad()}")
    print(f"{proyecto_b.nombre}: costo= ${proyecto_b.costo_total():.2f} | Viable = {proyecto_b.viabilidad()}")

    # 5) Intentar asignar un cuarto proyecto a un desarrollador y capturar el "error" (mensaje)
    proyecto_c = Proyecto("bios", 9000, responsable=gerente)
    proyecto_d = Proyecto("Microservicios", 11000, responsable=gerente)

    dev1.asignar_proyecto(proyecto_c)
    dev1.asignar_proyecto(proyecto_d)


    #Resumen de empleados
    print("\n == Resumen de empleados ==")
    for e in [gerente, dev1, dev2, dis1]:
        e.mostrar_informacion()
        print("-"*40)